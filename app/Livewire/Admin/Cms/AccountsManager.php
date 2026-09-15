<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Models\User;
use App\Services\Admin\CmsRolePermissionSynchronizer;
use App\Support\Admin\AdminNavigationRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Quản lý tài khoản')]
class AccountsManager extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public ?string $currentRouteName = null;

    public array $form = [];

    public string $roleFilter = '';

    public string $search = '';

    public ?int $selectedId = null;

    public string $statusFilter = '';

    public function mount(?User $user = null): void
    {
        $this->authorizeAccountManagerRole();

        $this->currentRouteName = request()->route()?->getName();
        $this->resetForm();

        if ($this->isEditorRoute() && $user) {
            $this->editAccount((int) $user->getKey());
        }
    }

    public function hydrate(): void
    {
        $this->authorizeAccountManagerRole();
    }

    public function createAccount(): void
    {
        $this->authorizeAccountAdministration();

        $this->selectedId = null;
        $this->resetForm();
    }

    public function editAccount(int $id): void
    {
        $this->authorizeAccountAdministration();

        $user = User::query()->with(['roles', 'permissions'])->findOrFail($id);
        $defaults = AdminNavigationRegistry::defaultContentPermissions();
        $grantablePermissions = AdminNavigationRegistry::grantableContentPermissionKeys();

        $this->selectedId = (int) $user->getKey();
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => '',
            'password_confirmation' => '',
            'role_type' => $this->roleTypeFor($user),
            'is_active' => (bool) $user->is_active,
            'extra_permissions' => $user->permissions
                ->pluck('name')
                ->reject(fn (string $permission) => in_array($permission, $defaults, true))
                ->filter(fn (string $permission) => in_array($permission, $grantablePermissions, true))
                ->values()
                ->all(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.cms.accounts-manager', [
            'accountGroups' => AdminNavigationRegistry::grantableContentGroups(),
            'canManageUserState' => $this->currentUserCanManageAccounts(),
            'currentUserId' => auth()->id(),
            'selectedUser' => $this->selectedId ? User::query()->find($this->selectedId) : null,
            'users' => User::query()
                ->with('roles')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($nested) {
                        $nested
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->roleFilter === 'content', fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'content')))
                ->when($this->roleFilter === 'sale', fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'sale')))
                ->when($this->roleFilter === 'admin', fn ($query) => $query->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->whereIn('name', ['content', 'sale'])))
                ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
                ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function save(): void
    {
        $this->authorizeAccountAdministration();

        $isEditing = $this->selectedId !== null;

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->selectedId),
            ],
            'form.phone' => ['nullable', 'string', 'max:50'],
            'form.password' => [$isEditing ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'form.role_type' => ['required', Rule::in(['admin', 'content', 'sale'])],
            'form.is_active' => ['boolean'],
            'form.extra_permissions' => ['array'],
            'form.extra_permissions.*' => ['string', Rule::in(AdminNavigationRegistry::grantableContentPermissionKeys())],
        ]);

        $user = User::query()->findOrNew($this->selectedId);
        $targetIsActive = (bool) ($validated['form']['is_active'] ?? true);

        if ($isEditing && $user->is(auth()->user()) && ! $targetIsActive) {
            $this->addError('form.is_active', 'Không thể tự tắt tài khoản đang đăng nhập.');

            return;
        }

        if ($isEditing && $user->is(auth()->user()) && $validated['form']['role_type'] !== 'admin') {
            $this->addError('form.role_type', 'Không thể tự hạ quyền tài khoản đang đăng nhập.');

            return;
        }

        if ($this->wouldRemoveLastActiveAdministrator($user, $validated['form']['role_type'], $targetIsActive)) {
            $this->addError('form.is_active', 'Phải giữ lại ít nhất một tài khoản Admin đang bật.');

            return;
        }

        $user->name = $validated['form']['name'];
        $user->email = $validated['form']['email'];
        $user->phone = $validated['form']['phone'] ?: null;
        $user->is_active = $targetIsActive;

        if (filled($validated['form']['password'] ?? null)) {
            $user->password = $validated['form']['password'];
        }

        $user->save();
        $this->synchronizeCmsAccess();

        if ($validated['form']['role_type'] === 'admin') {
            $role = $user->hasRole('super_admin') ? 'super_admin' : 'admin';
            $user->syncRoles([$role]);
            $user->syncPermissions([]);
        } elseif ($validated['form']['role_type'] === 'sale') {
            $user->syncRoles(['sale']);
            $user->syncPermissions([]);
        } else {
            $user->syncRoles(['content']);
            $defaultPermissions = AdminNavigationRegistry::defaultContentPermissions();
            $extraPermissions = collect($validated['form']['extra_permissions'] ?? [])
                ->reject(fn (string $permission) => in_array($permission, $defaultPermissions, true))
                ->values()
                ->all();

            $user->syncPermissions($extraPermissions);
        }

        if (! $user->is_active) {
            $this->clearUserSessions($user);
        }

        $this->selectedId = (int) $user->getKey();
        $this->editAccount((int) $user->getKey());

        session()->flash('status', 'Đã lưu tài khoản CMS.');

        $this->redirectRoute('admin.accounts.edit', ['user' => $user], navigate: true);
    }

    public function disableUser(int $id): void
    {
        $this->authorizeAccountAdministration();

        $user = $this->findUserForAccountAction($id);

        if (! $this->canDeactivateOrDelete($user)) {
            return;
        }

        $user->forceFill([
            'is_active' => false,
            'remember_token' => null,
        ])->save();

        $this->clearUserSessions($user);

        if ($this->selectedId === (int) $user->getKey()) {
            $this->editAccount((int) $user->getKey());
        }

        session()->flash('status', 'Đã tắt tài khoản '.$user->name.'.');
    }

    public function enableUser(int $id): void
    {
        $this->authorizeAccountAdministration();

        $user = $this->findUserForAccountAction($id);
        $user->forceFill(['is_active' => true])->save();

        if ($this->selectedId === (int) $user->getKey()) {
            $this->editAccount((int) $user->getKey());
        }

        session()->flash('status', 'Đã bật lại tài khoản '.$user->name.'.');
    }

    public function deleteUser(int $id): void
    {
        $this->authorizeAccountAdministration();

        $user = $this->findUserForAccountAction($id);

        if (! $this->canDeactivateOrDelete($user)) {
            return;
        }

        $deletedName = $user->name;

        $this->clearUserSessions($user);
        $user->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
            $this->resetForm();
        }

        session()->flash('status', 'Đã xóa tài khoản '.$deletedName.'.');

        if ($this->isEditorRoute()) {
            $this->redirectRoute('admin.accounts', navigate: true);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function isEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.accounts.create', 'admin.accounts.edit'], true);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'password' => '',
            'password_confirmation' => '',
            'role_type' => 'content',
            'is_active' => true,
            'extra_permissions' => [],
        ];
    }

    protected function roleTypeFor(User $user): string
    {
        if ($user->hasRole('sale')) {
            return 'sale';
        }

        return $user->hasRole('content') ? 'content' : 'admin';
    }

    protected function synchronizeCmsAccess(): void
    {
        app(CmsRolePermissionSynchronizer::class)->sync();
    }

    protected function authorizeAccountAdministration(): void
    {
        $this->authorizeAdminPermission('admin.accounts.edit');
        $this->authorizeAccountManagerRole();
    }

    protected function authorizeAccountManagerRole(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['admin', 'super_admin']),
            403,
            'Chỉ Admin được phép quản lý tài khoản.',
        );
    }

    protected function currentUserCanManageAccounts(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('admin.accounts.edit') && $user->hasAnyRole(['admin', 'super_admin']));
    }

    protected function findUserForAccountAction(int $id): User
    {
        return User::query()->with('roles')->findOrFail($id);
    }

    protected function canDeactivateOrDelete(User $user): bool
    {
        if ($user->is(auth()->user())) {
            $this->addError('account_action', 'Không thể tắt hoặc xóa tài khoản đang đăng nhập.');

            return false;
        }

        if ($this->wouldRemoveLastActiveAdministrator($user, 'content', false)) {
            $this->addError('account_action', 'Phải giữ lại ít nhất một tài khoản Admin đang bật.');

            return false;
        }

        return true;
    }

    protected function wouldRemoveLastActiveAdministrator(User $user, string $targetRoleType, bool $targetIsActive): bool
    {
        if (! $user->exists || ! $user->is_active || ! $user->hasAnyRole(['admin', 'super_admin'])) {
            return false;
        }

        if ($targetIsActive && $targetRoleType === 'admin') {
            return false;
        }

        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super_admin']))
            ->doesntExist();
    }

    protected function clearUserSessions(User $user): void
    {
        $user->forceFill(['remember_token' => null])->save();

        DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
