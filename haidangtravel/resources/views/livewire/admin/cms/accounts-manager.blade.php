@php
    $defaults = \App\Support\Admin\AdminNavigationRegistry::defaultContentPermissions();
    $isEditorRoute = in_array($currentRouteName, ['admin.accounts.create', 'admin.accounts.edit'], true);
    $canManageUserState = $canManageUserState ?? false;
    $currentUserId = $currentUserId ?? auth()->id();
@endphp

<div class="space-y-4">
    @if ($isEditorRoute)
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập tài khoản' : 'Tạo tài khoản CMS' }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang chi tiết dùng riêng cho cấu hình vai trò và ma trận quyền, không trộn chung với danh sách.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($selectedUser && $canManageUserState && $selectedUser->id !== $currentUserId)
                    @if ($selectedUser->is_active)
                        <button type="button" wire:click="disableUser({{ $selectedUser->id }})" wire:confirm="Bạn có chắc chắn muốn tắt tài khoản này? User sẽ không thể đăng nhập CMS cho đến khi được bật lại." class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 dark:border-amber-500/30 dark:text-amber-300 dark:hover:bg-amber-500/10">
                            <i class="fa-solid fa-user-slash"></i>
                            Tắt tài khoản
                        </button>
                    @else
                        <button type="button" wire:click="enableUser({{ $selectedUser->id }})" class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
                            <i class="fa-solid fa-user-check"></i>
                            Bật tài khoản
                        </button>
                    @endif

                    <button type="button" wire:click="deleteUser({{ $selectedUser->id }})" wire:confirm="Bạn có chắc chắn muốn xóa tài khoản này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                        <i class="fa-solid fa-trash"></i>
                        Xóa
                    </button>
                @endif

                <a href="{{ route('admin.accounts') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    <i class="fa-solid fa-arrow-left"></i>
                    Về danh sách
                </a>
            </div>
        </div>

        <x-admin.form-feedback />

        <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            @can('admin.accounts.edit')
                <form wire:submit="save" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu tài khoản...">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Họ tên</label>
                            <input type="text" wire:model.defer="form.name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                            <input type="email" wire:model.defer="form.email" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số điện thoại</label>
                            <input type="text" wire:model.defer="form.phone" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mật khẩu {{ $selectedId ? '(để trống nếu không đổi)' : '' }}</label>
                            <input type="password" wire:model.defer="form.password" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Xác nhận mật khẩu</label>
                            <input type="password" wire:model.defer="form.password_confirmation" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <label class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 md:col-span-2">
                            <input type="checkbox" wire:model.defer="form.is_active" class="mt-1 rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                            <span>
                                <span class="block font-semibold text-zinc-900 dark:text-white">Tài khoản đang bật</span>
                                <span class="mt-1 block text-zinc-500 dark:text-zinc-400">Khi tắt, user không thể đăng nhập CMS và session hiện tại của user đó sẽ bị xóa.</span>
                            </span>
                        </label>
                    </div>

                    <section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Loại tài khoản</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">`Admin` có full quyền. `Content` mặc định chỉ được sửa blog. `Sale` chỉ can thiệp tour do chính tài khoản đó quản lý.</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                <input type="radio" wire:model.defer="form.role_type" value="admin" class="mt-1 rounded border-zinc-300 text-teal-600">
                                <span>
                                    <span class="block font-semibold text-zinc-900 dark:text-white">Admin</span>
                                    <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Full quyền trên toàn bộ sidebar và tất cả action.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                <input type="radio" wire:model.defer="form.role_type" value="content" class="mt-1 rounded border-zinc-300 text-teal-600">
                                <span>
                                    <span class="block font-semibold text-zinc-900 dark:text-white">Content</span>
                                    <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Mặc định có blog + danh mục blog, sau đó cấp thêm quyền theo action.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                <input type="radio" wire:model.defer="form.role_type" value="sale" class="mt-1 rounded border-zinc-300 text-teal-600">
                                <span>
                                    <span class="block font-semibold text-zinc-900 dark:text-white">Sale</span>
                                    <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Chỉ tạo, xem và sửa các tour được gán cho chính tài khoản này.</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    @if (($form['role_type'] ?? 'content') === 'content')
                        <section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Quyền bổ sung theo sidebar</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Giữ nguyên quyền mặc định của Content và chỉ bật thêm các action cần thiết.</p>
                            </div>

                            <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Quyền mặc định:
                                <span class="font-medium">{{ implode(', ', array_values(array_filter($defaults, fn ($permission) => $permission !== 'access admin panel'))) }}</span>
                            </div>

                            <div class="space-y-4">
                                @foreach ($accountGroups as $group)
                                    <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                        <div class="mb-3">
                                            <h4 class="font-semibold text-zinc-900 dark:text-white">{{ $group['label'] }}</h4>
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Chỉ hiện các action mà tài khoản này thực sự được phép dùng.</p>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            @foreach ($group['actions'] as $action)
                                                @php
                                                    $isDefault = in_array($action['permission'], $defaults, true);
                                                @endphp
                                                <label class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-800 dark:bg-zinc-950/40">
                                                    <input
                                                        type="checkbox"
                                                        value="{{ $action['permission'] }}"
                                                        wire:model.defer="form.extra_permissions"
                                                        @disabled($isDefault)
                                                        class="mt-1 rounded border-zinc-300 text-teal-600"
                                                    >
                                                    <span>
                                                        <span class="block font-medium text-zinc-900 dark:text-white">{{ $action['label'] }}</span>
                                                        <span class="mt-1 block text-zinc-500 dark:text-zinc-400">{{ $action['description'] }}</span>
                                                        <span class="mt-1 block text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $action['permission'] }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <x-admin.form-action-bar>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-3 text-sm font-semibold text-white">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Lưu tài khoản
                        </button>
                    </x-admin.form-action-bar>
                </form>
            @else
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Bạn có thể xem danh sách tài khoản nhưng không có quyền chỉnh sửa.
                </div>
            @endcan
        </section>
    @else
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách tài khoản</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Index riêng để lọc, kiểm tra vai trò và đi nhanh sang màn cấp quyền.</p>
            </div>

            @can('admin.accounts.edit')
                <a href="{{ route('admin.accounts.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                    <i class="fa-solid fa-user-plus"></i>
                    Tài khoản mới
                </a>
            @endcan
        </div>

        <x-admin.form-feedback />

        <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 grid gap-3 xl:grid-cols-3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc email..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <select wire:model.live="roleFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="content">Content</option>
                    <option value="sale">Sale</option>
                </select>

                <select wire:model.live="statusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang bật</option>
                    <option value="inactive">Đã tắt</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                        <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                            <th class="px-4 py-3">Tài khoản</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Vai trò</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Truy cập</th>
                            <th class="px-4 py-3 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @forelse ($users as $user)
                            @php
                                $roleLabel = $user->roles->contains('name', 'sale')
                                    ? 'Sale'
                                    : ($user->roles->contains('name', 'content') ? 'Content' : 'Admin');
                            @endphp
                            <tr wire:key="account-row-{{ $user->id }}" class="align-top {{ $user->is_active ? '' : 'bg-zinc-50/80 dark:bg-zinc-950/40' }}">
                                <td class="px-4 py-4 font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $user->email }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $roleLabel === 'Content' ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' : ($roleLabel === 'Sale' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300') }}">
                                        {{ $roleLabel }}
                                    </span>
                                    @if ($user->roles->contains('name', 'super_admin'))
                                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-300">compat: super_admin</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                        {{ $user->is_active ? 'Đang bật' : 'Đã tắt' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                    {{ $roleLabel === 'Admin' ? 'Toàn bộ sidebar/action' : ($roleLabel === 'Sale' ? 'Chỉ tour do tài khoản này quản lý' : 'Blog mặc định + quyền bổ sung nếu có') }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @can('admin.accounts.edit')
                                            <a href="{{ route('admin.accounts.edit', $user) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                Sửa
                                            </a>

                                            @if ($canManageUserState && $user->id !== $currentUserId)
                                                @if ($user->is_active)
                                                    <button type="button" wire:click="disableUser({{ $user->id }})" wire:confirm="Bạn có chắc chắn muốn tắt tài khoản này? User sẽ không thể đăng nhập CMS cho đến khi được bật lại." class="rounded-2xl border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50 dark:border-amber-500/30 dark:text-amber-300 dark:hover:bg-amber-500/10">
                                                        Tắt
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="enableUser({{ $user->id }})" class="rounded-2xl border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
                                                        Bật
                                                    </button>
                                                @endif

                                                <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="Bạn có chắc chắn muốn xóa tài khoản này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                                    Xóa
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có tài khoản nào khớp bộ lọc hiện tại.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </section>
    @endif
</div>
