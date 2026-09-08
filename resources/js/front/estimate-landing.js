function initEstimateLanding() {
    const landing = document.querySelector('[data-estimate-landing]');

    if (!landing || landing.dataset.estimateInitialized === 'true') {
        return;
    }

    landing.dataset.estimateInitialized = 'true';

    const estimatorUiNode = landing.querySelector('[data-estimate-ui]');
    let estimatorUi = {};

    try {
        estimatorUi = JSON.parse(estimatorUiNode?.textContent || '{}');
    } catch (error) {
        estimatorUi = {};
    }

    const tabs = Array.from(landing.querySelectorAll('[data-estimate-tab]'));
    const fieldRows = Array.from(landing.querySelectorAll('[data-estimator-field]'));
    const requestButtons = Array.from(landing.querySelectorAll('[data-estimate-request-open]'));
    const guideButtons = Array.from(landing.querySelectorAll('[data-estimate-guide-open]'));
    const summaryList = landing.querySelector('[data-estimate-input-summary]');
    const formAlert = landing.querySelector('[data-estimate-form-alert]');
    const breakdownRows = landing.querySelector('[data-estimate-breakdown-rows]');
    const pricingCards = landing.querySelector('[data-estimate-pricing-cards]');
    const illustrationScene = landing.querySelector('[data-estimate-illustration-scene]');
    const totalConvertedArea = landing.querySelector('[data-estimate-total-converted-area]');
    const totalBaseArea = landing.querySelector('[data-estimate-total-base-area]');
    const formulaVersion = landing.querySelector('[data-estimate-formula-version]');
    const activeTierName = landing.querySelector('[data-estimate-active-tier-name]');
    const activeLevelLabel = landing.querySelector('[data-estimate-active-level-label]');
    const activeBadge = landing.querySelector('[data-estimate-active-badge]');
    const keyStatus = landing.querySelector('[data-estimate-key-status]');
    const previewSheets = Array.from(document.querySelectorAll('[data-estimate-preview-sheet]'));
    const initialGroupedStateNode = landing.querySelector('[data-estimate-initial-grouped-state]');
    const componentTreeContainer = landing.querySelector('[data-estimate-component-tree]');
    const roomBreakdownContainer = landing.querySelector('[data-estimate-room-breakdown]');
    const roomProgramSection = landing.querySelector('[data-estimate-room-program-section]');
    const roomProgramList = landing.querySelector('[data-estimate-room-program-list]');
    const roomProgramEmpty = landing.querySelector('[data-estimate-room-program-empty]');
    const roomProgramAddButton = landing.querySelector('[data-estimate-room-program-add]');
    const designOptionsSection = landing.querySelector('[data-estimate-design-options-section]');
    const designOptionsList = landing.querySelector('[data-estimate-design-options-list]');
    const specialAddonsSection = landing.querySelector('[data-estimate-special-addons-section]');
    const specialAddonsList = landing.querySelector('[data-estimate-special-addons-list]');
    const modal = document.querySelector('[data-estimate-request-modal]');
    const modalTierCode = modal?.querySelector('[data-estimate-modal-tier-code]');
    const modalTierName = modal?.querySelector('[data-estimate-modal-tier-name]');
    const modalLevel = modal?.querySelector('[data-estimate-modal-level]');
    const modalInputPayload = modal?.querySelector('[data-estimate-modal-input-payload]');
    const modalResultPayload = modal?.querySelector('[data-estimate-modal-result-payload]');
    const modalPageUrl = modal?.querySelector('[data-estimate-modal-page-url]');
    const modalTierLabel = modal?.querySelector('[data-estimate-modal-tier-label]');
    const modalDeliveryText = modal?.querySelector('[data-estimate-modal-delivery]');
    const modalTitle = modal?.querySelector('[data-estimate-modal-title]');
    const modalDescription = modal?.querySelector('[data-estimate-modal-description]');
    const modalKeyWrap = modal?.querySelector('[data-estimate-key-field]');
    const modalKeyLabel = modal?.querySelector('[data-estimate-key-label]');
    const modalKeyHelp = modal?.querySelector('[data-estimate-key-help]');
    const defaultModalTitle = modal?.dataset.defaultTitle || modalTitle?.textContent || '';
    const defaultModalDescription = modal?.dataset.defaultDescription || modalDescription?.textContent || '';
    const guideModal = document.querySelector('[data-estimate-guide-modal]');
    const guideModalTitle = guideModal?.querySelector('[data-estimate-guide-title]');
    const guideModalDescription = guideModal?.querySelector('[data-estimate-guide-description]');
    const guideModalLevelLabel = guideModal?.querySelector('[data-estimate-guide-level-label]');
    const guideModalList = guideModal?.querySelector('[data-estimate-guide-list]');
    const defaultGuideModalTitle = guideModal?.dataset.defaultTitle || guideModalTitle?.textContent || '';
    const defaultGuideModalDescription = guideModal?.dataset.defaultDescription || guideModalDescription?.textContent || '';
    let activeGuideFieldKey = null;
    let lastTerraceEditedFieldKey = null;
    let initialGroupedState = {};
    const areaFormatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 });
    const currencyFormatter = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    });

    try {
        initialGroupedState = JSON.parse(initialGroupedStateNode?.textContent || '{}');
    } catch (error) {
        initialGroupedState = {};
    }

    const escapeHtml = (value) => `${value ?? ''}`
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const parseLevelList = (value) => `${value ?? ''}`
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);

    const roundArea = (value) => {
        const numeric = Number(value);

        if (!Number.isFinite(numeric)) {
            return 0;
        }

        return Math.round(numeric * 100) / 100;
    };

    const formatArea = (value) => `${areaFormatter.format(roundArea(value))} m2`;
    const formatPercent = (value) => `${areaFormatter.format(roundArea(Number(value || 0) * 100))}%`;
    const formatCurrency = (value) => currencyFormatter.format(Number(value || 0));
    const maskCurrency = (value) => {
        const amount = Math.max(0, Math.round(Number(value || 0)));
        const digits = `${amount}`;

        if (!Number.isFinite(amount) || amount <= 0) {
            return '0 đ';
        }

        const maskedDigits = `${digits[0]}${'x'.repeat(Math.max(0, digits.length - 1))}`;
        const firstGroupLength = maskedDigits.length % 3 || 3;
        const groups = [maskedDigits.slice(0, firstGroupLength)];

        for (let index = firstGroupLength; index < maskedDigits.length; index += 3) {
            groups.push(maskedDigits.slice(index, index + 3));
        }

        return `${groups.join('.')} đ`;
    };
    const getFieldMeta = (key) => estimatorUi?.fields?.[key] || {};
    const getFieldOptions = (field) => Array.isArray(field?.options) ? field.options : [];
    const getFieldRow = (key) => fieldRows.find((row) => row.dataset.fieldKey === key) || null;
    const getFieldControl = (key) => getFieldRow(key)?.querySelector('[data-estimate-input]') || null;
    const catalogTree = Array.isArray(estimatorUi?.catalog) ? estimatorUi.catalog : [];
    const catalogItems = Array.isArray(estimatorUi?.catalog_items) ? estimatorUi.catalog_items : [];
    const roomTemplates = Array.isArray(estimatorUi?.room_templates) ? estimatorUi.room_templates : [];
    const roomTypes = Array.isArray(estimatorUi?.room_types) ? estimatorUi.room_types : [];
    const priceBooks = Array.isArray(estimatorUi?.price_books) ? estimatorUi.price_books : [];
    const levelDefinitions = Array.isArray(estimatorUi?.levels) ? estimatorUi.levels : [];
    const initialLevelOneFields = (levelDefinitions.find((level) => level.code === 'level_1')?.field_keys || []);
    const internalFieldKeys = (estimatorUi?.groups || []).find((group) => group?.key === 'commercial')?.fields || [];
    const flattenCatalogTree = (nodes, parentCode = null) => (Array.isArray(nodes) ? nodes : [])
        .flatMap((node) => [
            {
                code: node.code,
                name: node.name,
                node_type: node.node_type,
                parent_code: parentCode,
                building_types: Array.isArray(node.building_types) ? node.building_types : [],
            },
            ...flattenCatalogTree(node.children || [], node.code),
        ]);
    const nodeList = flattenCatalogTree(catalogTree);
    const nodeByCode = new Map(nodeList.map((node) => [node.code, node]));
    const itemByCode = new Map(catalogItems.map((item) => [item.code, item]));
    const roomTemplateByCode = new Map(roomTemplates.map((template) => [template.code, template]));
    const roomTypeByCode = new Map(roomTypes.map((roomType) => [roomType.code, roomType]));
    const pricingModeLabels = {
        per_m2: 'Theo m2',
        per_room: 'Theo phòng',
        per_item: 'Theo món',
        per_floor: 'Theo tầng',
        fixed: 'Trọn gói',
        percent_base: '% nền',
    };
    const normalizeCodeValue = (value) => {
        if (value && typeof value === 'object') {
            return `${value.code || value.value || ''}`.trim();
        }

        return `${value ?? ''}`.trim();
    };
    const uniqueCodes = (values) => Array.from(new Set((Array.isArray(values) ? values : [])
        .map((value) => normalizeCodeValue(value))
        .filter(Boolean)));
    let roomProgramState = (Array.isArray(initialGroupedState?.room_program) ? initialGroupedState.room_program : []).map((row) => ({
        room_type: normalizeCodeValue(row?.room_type),
        count: Math.max(1, Math.trunc(Number(row?.count || 1))),
        template: normalizeCodeValue(row?.template),
        extra_items: uniqueCodes(Array.isArray(row?.extra_items) ? row.extra_items : []),
    }));
    let designOptionState = uniqueCodes(initialGroupedState?.design_options);
    let specialAddonState = uniqueCodes(initialGroupedState?.special_addons);
    const formatInputNumber = (value) => {
        const numeric = roundArea(value);

        return Number.isFinite(numeric) ? `${numeric}` : '';
    };
    const setControlNumericValue = (key, value) => {
        const control = getFieldControl(key);

        if (!control) {
            return;
        }

        const nextValue = Number.isFinite(Number(value)) ? formatInputNumber(value) : '';

        if (`${control.value ?? ''}` !== nextValue) {
            control.value = nextValue;
        }
    };
    const fieldGroupLabel = (fieldKey) => {
        const group = (estimatorUi?.groups || []).find((item) => Array.isArray(item?.fields) && item.fields.includes(fieldKey));

        return group?.label || 'Nhóm nhập liệu';
    };
    const formatGuideContent = (value) => {
        const resolved = `${value ?? ''}`.trim();

        if (resolved === '') {
            return '';
        }

        return /<[^>]+>/.test(resolved)
            ? resolved
            : escapeHtml(resolved).replace(/\n/g, '<br>');
    };

    const optionLabel = (field, value) => {
        const matched = getFieldOptions(field).find((option) => `${option.value}` === `${value}`);

        return matched?.label || `${value ?? ''}`.trim();
    };

    const displayValue = (field, value) => {
        const type = `${field?.type || 'text'}`;
        const unit = `${field?.unit || ''}`.trim();

        if (value === null || value === undefined || value === '') {
            return '';
        }

        if (type === 'boolean') {
            return value ? 'Có' : 'Không';
        }

        if (type === 'multiselect') {
            return (Array.isArray(value) ? value : [])
                .map((item) => optionLabel(field, item))
                .filter(Boolean)
                .join(', ');
        }

        if (getFieldOptions(field).length > 0) {
            return optionLabel(field, value);
        }

        if (type === 'number' || type === 'integer') {
            const formatted = areaFormatter.format(Number(value));

            return unit ? `${formatted} ${unit}` : formatted;
        }

        const resolved = `${value}`.trim();

        return unit && resolved !== '' ? `${resolved} ${unit}` : resolved;
    };

    const isEmptyValue = (value) => {
        if (Array.isArray(value)) {
            return value.length === 0;
        }

        return value === null || value === undefined || `${value}`.trim() === '';
    };

    const selectedTab = () => tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0] || null;
    const activeLevelCode = () => selectedTab()?.dataset.levelCode || 'level_1';
    const levelRows = (levelCode = activeLevelCode()) => fieldRows.filter((row) => parseLevelList(row.dataset.visibleLevels).includes(levelCode));

    const fieldControlValue = (row, field) => {
        const type = `${field?.type || row.dataset.fieldType || 'text'}`;
        const controls = Array.from(row.querySelectorAll('[data-estimate-input]'));

        if (type === 'multiselect') {
            return controls
                .filter((control) => control.checked)
                .map((control) => `${control.value}`.trim())
                .filter(Boolean);
        }

        const control = controls[0];

        if (!control) {
            return null;
        }

        const rawValue = `${control.value ?? ''}`.trim();

        if (rawValue === '') {
            return null;
        }

        if (type === 'boolean') {
            return rawValue === 'true';
        }

        if (type === 'number') {
            const parsed = Number(rawValue);

            return Number.isFinite(parsed) ? parsed : null;
        }

        if (type === 'integer') {
            const parsed = Number(rawValue);

            return Number.isFinite(parsed) ? Math.trunc(parsed) : null;
        }

        return rawValue;
    };

    const buildLevelInputMap = (levelCode = activeLevelCode()) => levelRows(levelCode)
        .reduce((carry, row) => {
            const key = row.dataset.fieldKey || '';

            if (key === '') {
                return carry;
            }

            carry[key] = fieldControlValue(row, getFieldMeta(key));

            return carry;
        }, {});
    const levelDefinition = (levelCode = activeLevelCode()) => levelDefinitions.find((level) => level.code === levelCode) || {};
    const currentBuildingType = (levelCode = activeLevelCode(), inputMap = buildLevelInputMap(levelCode)) => normalizeCodeValue(inputMap.building_type);
    const supportsLevel = (entry, levelCode = activeLevelCode()) => {
        const levels = Array.isArray(entry?.level_support) ? entry.level_support : [];

        return levels.length === 0 || levels.includes(levelCode);
    };
    const matchesBuildingType = (entry, buildingType = currentBuildingType()) => {
        const buildingTypes = Array.isArray(entry?.building_types) ? entry.building_types : [];

        return !buildingType || buildingTypes.length === 0 || buildingTypes.includes(buildingType);
    };
    const compatibleRoomTypes = (levelCode = activeLevelCode(), buildingType = currentBuildingType(levelCode)) => roomTypes
        .filter((roomType) => matchesBuildingType(roomType, buildingType))
        .sort((left, right) => `${left.label || left.name || ''}`.localeCompare(`${right.label || right.name || ''}`, 'vi'));
    const compatibleTemplates = (roomTypeCode, levelCode = activeLevelCode(), buildingType = currentBuildingType(levelCode)) => roomTemplates
        .filter((template) => template.room_type === roomTypeCode && matchesBuildingType(template, buildingType))
        .sort((left, right) => Number(left.sort_order || 0) - Number(right.sort_order || 0));
    const compatibleRoomExtraItems = (roomTypeCode, levelCode = activeLevelCode(), buildingType = currentBuildingType(levelCode)) => catalogItems
        .filter((item) => supportsLevel(item, levelCode)
            && matchesBuildingType(item, buildingType)
            && ['interior', 'addon'].includes(`${item.item_type || ''}`)
            && (item.component_node_code === roomTypeCode || item.component_node_code === 'interior_finish'))
        .sort((left, right) => `${left.name || ''}`.localeCompare(`${right.name || ''}`, 'vi'));
    const compatibleDesignItems = (levelCode = activeLevelCode(), buildingType = currentBuildingType(levelCode)) => catalogItems
        .filter((item) => supportsLevel(item, levelCode) && matchesBuildingType(item, buildingType) && `${item.item_type || ''}` === 'design')
        .sort((left, right) => `${left.name || ''}`.localeCompare(`${right.name || ''}`, 'vi'));
    const compatibleSpecialAddons = (levelCode = activeLevelCode(), buildingType = currentBuildingType(levelCode)) => catalogItems
        .filter((item) => supportsLevel(item, levelCode)
            && matchesBuildingType(item, buildingType)
            && !['design', 'interior'].includes(`${item.item_type || ''}`)
            && !['elevator_standard', 'pool_standard', 'pool_overflow', 'pool_jacuzzi', 'loose_furniture_upgrade'].includes(`${item.code || ''}`))
        .sort((left, right) => `${left.name || ''}`.localeCompare(`${right.name || ''}`, 'vi'));
    const defaultRoomProgramRow = (levelCode = activeLevelCode(), buildingType = currentBuildingType(levelCode)) => {
        const roomType = compatibleRoomTypes(levelCode, buildingType)[0]?.code || '';
        const template = compatibleTemplates(roomType, levelCode, buildingType)[0]?.code || '';

        return {
            room_type: roomType,
            count: 1,
            template,
            extra_items: [],
        };
    };
    const sanitizeRoomProgramState = (levelCode = activeLevelCode(), inputMap = buildLevelInputMap(levelCode)) => {
        const buildingType = currentBuildingType(levelCode, inputMap);
        const allowedRoomTypes = compatibleRoomTypes(levelCode, buildingType).map((roomType) => roomType.code);

        roomProgramState = roomProgramState
            .map((row) => {
                const roomType = allowedRoomTypes.includes(normalizeCodeValue(row?.room_type))
                    ? normalizeCodeValue(row?.room_type)
                    : '';
                const templates = compatibleTemplates(roomType, levelCode, buildingType);
                const extraItems = compatibleRoomExtraItems(roomType, levelCode, buildingType).map((item) => item.code);
                const template = templates.some((item) => item.code === normalizeCodeValue(row?.template))
                    ? normalizeCodeValue(row?.template)
                    : (templates[0]?.code || '');

                return {
                    room_type: roomType,
                    count: Math.max(1, Math.trunc(Number(row?.count || 1))),
                    template,
                    extra_items: uniqueCodes(row?.extra_items).filter((code) => extraItems.includes(code)),
                };
            })
            .filter((row) => row.room_type !== '' || row.template !== '' || row.extra_items.length > 0);

        designOptionState = designOptionState.filter((code) => compatibleDesignItems(levelCode, buildingType).some((item) => item.code === code));
        specialAddonState = specialAddonState.filter((code) => compatibleSpecialAddons(levelCode, buildingType).some((item) => item.code === code));

        return {
            room_program: roomProgramState,
            design_options: designOptionState,
            special_addons: specialAddonState,
        };
    };

    const baseAreaFromMap = (inputMap) => roundArea(Number(inputMap.length || 0) * Number(inputMap.width || 0));
    const terraceRooftopAreaFromMap = (inputMap) => roundArea(baseAreaFromMap(inputMap) * 1.25);

    const syncTerraceInputs = (sourceKey = null) => {
        const levelCode = activeLevelCode();
        const levelInputMap = buildLevelInputMap(levelCode);
        const hasTerrace = levelInputMap.has_terrace === true;
        const tumControl = getFieldControl('tum_area');
        const terraceControl = getFieldControl('terrace_area');

        if (!tumControl || !terraceControl) {
            return;
        }

        if (sourceKey === 'tum_area' || sourceKey === 'terrace_area') {
            lastTerraceEditedFieldKey = sourceKey;
        }

        if (!hasTerrace) {
            lastTerraceEditedFieldKey = null;
            return;
        }

        const terraceRooftopArea = terraceRooftopAreaFromMap(levelInputMap);

        if (!Number.isFinite(terraceRooftopArea) || terraceRooftopArea <= 0) {
            return;
        }

        const defaultTumArea = roundArea(terraceRooftopArea * 0.3);
        const defaultTerraceArea = roundArea(terraceRooftopArea - defaultTumArea);
        const rawTumValue = `${tumControl.value ?? ''}`.trim();
        const rawTerraceValue = `${terraceControl.value ?? ''}`.trim();
        const currentTumValue = Number(rawTumValue);
        const currentTerraceValue = Number(rawTerraceValue);
        const hasTumValue = rawTumValue !== '' && Number.isFinite(currentTumValue);
        const hasTerraceValue = rawTerraceValue !== '' && Number.isFinite(currentTerraceValue);
        const clampArea = (value) => roundArea(Math.min(terraceRooftopArea, Math.max(0, Number(value || 0))));

        let nextTumArea = defaultTumArea;
        let nextTerraceArea = defaultTerraceArea;

        if (sourceKey === 'has_terrace') {
            nextTumArea = defaultTumArea;
            nextTerraceArea = defaultTerraceArea;
        } else if (sourceKey === 'tum_area') {
            nextTumArea = clampArea(hasTumValue ? currentTumValue : defaultTumArea);
            nextTerraceArea = roundArea(terraceRooftopArea - nextTumArea);
        } else if (sourceKey === 'terrace_area') {
            nextTerraceArea = clampArea(hasTerraceValue ? currentTerraceValue : defaultTerraceArea);
            nextTumArea = roundArea(terraceRooftopArea - nextTerraceArea);
        } else if (sourceKey === 'length' || sourceKey === 'width') {
            if (lastTerraceEditedFieldKey === 'terrace_area' && hasTerraceValue) {
                nextTerraceArea = clampArea(currentTerraceValue);
                nextTumArea = roundArea(terraceRooftopArea - nextTerraceArea);
            } else if (hasTumValue) {
                nextTumArea = clampArea(currentTumValue);
                nextTerraceArea = roundArea(terraceRooftopArea - nextTumArea);
            }
        } else if (rawTumValue === '' && rawTerraceValue === '') {
            nextTumArea = defaultTumArea;
            nextTerraceArea = defaultTerraceArea;
        } else if (rawTumValue === '' && hasTerraceValue) {
            nextTerraceArea = clampArea(currentTerraceValue);
            nextTumArea = roundArea(terraceRooftopArea - nextTerraceArea);
        } else if (rawTerraceValue === '' && hasTumValue) {
            nextTumArea = clampArea(currentTumValue);
            nextTerraceArea = roundArea(terraceRooftopArea - nextTumArea);
        } else if (lastTerraceEditedFieldKey === 'terrace_area' && hasTerraceValue) {
            nextTerraceArea = clampArea(currentTerraceValue);
            nextTumArea = roundArea(terraceRooftopArea - nextTerraceArea);
        } else if (hasTumValue) {
            nextTumArea = clampArea(currentTumValue);
            nextTerraceArea = roundArea(terraceRooftopArea - nextTumArea);
        }

        setControlNumericValue('tum_area', nextTumArea);
        setControlNumericValue('terrace_area', nextTerraceArea);
    };
    const syncDynamicDefaults = (sourceKey = null) => {
        const levelCode = activeLevelCode();
        const levelInputMap = buildLevelInputMap(levelCode);
        const floors = Math.max(1, Math.trunc(Number(levelInputMap.floors || 1)));
        const elevatorControl = getFieldControl('elevator_service_floors');
        const poolTypeControl = getFieldControl('pool_type');

        if (levelInputMap.has_elevator === true && elevatorControl) {
            const rawValue = `${elevatorControl.value ?? ''}`.trim();

            if (sourceKey === 'has_elevator' || sourceKey === 'floors' || rawValue === '' || !Number.isFinite(Number(rawValue))) {
                setControlNumericValue('elevator_service_floors', floors);
            }
        }

        if (levelInputMap.has_pool === true && poolTypeControl && `${poolTypeControl.value ?? ''}`.trim() === '') {
            poolTypeControl.value = 'skimmer';
        }
    };

    const dependencyValue = (rawValue, type = 'text') => {
        if (type === 'boolean') {
            return `${rawValue}` === 'true';
        }

        if (type === 'number' || type === 'integer') {
            const parsed = Number(rawValue);

            return Number.isFinite(parsed) ? parsed : null;
        }

        return `${rawValue ?? ''}`.trim();
    };

    const dependencySatisfied = (row, inputMap) => {
        const dependsOnField = `${row.dataset.dependsOnField || ''}`.trim();

        if (dependsOnField === '') {
            return true;
        }

        const parentField = getFieldMeta(dependsOnField);
        const expectedValue = dependencyValue(row.dataset.dependsOnValue, parentField?.type || 'text');
        const actualValue = inputMap[dependsOnField];

        if (Array.isArray(actualValue)) {
            return actualValue.includes(expectedValue);
        }

        if (actualValue === null || actualValue === undefined || actualValue === '') {
            return false;
        }

        return actualValue === expectedValue;
    };

    const visibleRows = (levelCode = activeLevelCode(), inputMap = buildLevelInputMap(levelCode)) => levelRows(levelCode)
        .filter((row) => dependencySatisfied(row, inputMap));

    const buildInputMap = (levelCode = activeLevelCode(), inputMap = buildLevelInputMap(levelCode)) => visibleRows(levelCode, inputMap)
        .reduce((carry, row) => {
            const key = row.dataset.fieldKey || '';

            if (key === '') {
                return carry;
            }

            carry[key] = inputMap[key];

            return carry;
        }, {});

    const buildFlatPayload = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => visibleRows(levelCode, buildLevelInputMap(levelCode))
        .map((row) => {
            const key = row.dataset.fieldKey || '';
            const value = inputMap[key];

            if (key === '' || isEmptyValue(value)) {
                return null;
            }

            return { slug: key, value };
        })
        .filter(Boolean);
    const buildGroupedPayload = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => {
        const groupedState = sanitizeRoomProgramState(levelCode, inputMap);
        const baseFieldKeys = new Set(levelDefinition('level_1').field_keys || initialLevelOneFields);
        const internalKeys = new Set(internalFieldKeys);
        const payload = {
            base_inputs: {},
            technical_inputs: {},
            room_program: groupedState.room_program,
            design_options: groupedState.design_options,
            special_addons: groupedState.special_addons,
            internal_controls: {},
        };

        visibleRows(levelCode, buildLevelInputMap(levelCode)).forEach((row) => {
            const key = row.dataset.fieldKey || '';
            const value = inputMap[key];

            if (key === '' || isEmptyValue(value)) {
                return;
            }

            if (internalKeys.has(key)) {
                payload.internal_controls[key] = value;
                return;
            }

            if (baseFieldKeys.has(key)) {
                payload.base_inputs[key] = value;
                return;
            }

            payload.technical_inputs[key] = value;
        });

        return payload;
    };
    const buildPayload = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => (
        levelCode === 'level_1'
            ? buildFlatPayload(levelCode, inputMap)
            : buildGroupedPayload(levelCode, inputMap)
    );

    const buildDisplayItems = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => {
        const baseItems = visibleRows(levelCode, buildLevelInputMap(levelCode))
            .map((row) => {
            const key = row.dataset.fieldKey || '';
            const field = getFieldMeta(key);
            const value = inputMap[key];

            if (key === '' || isEmptyValue(value)) {
                return null;
            }

            return {
                label: field.label || key,
                value,
                displayValue: displayValue(field, value),
            };
        })
            .filter(Boolean);
        const customState = sanitizeRoomProgramState(levelCode, inputMap);
        const roomItems = customState.room_program.map((row, index) => {
            const roomLabel = roomTypeByCode.get(row.room_type)?.label || row.room_type;
            const templateLabel = roomTemplateByCode.get(row.template)?.name || 'Chưa chọn mẫu';
            const extraLabels = row.extra_items
                .map((code) => itemByCode.get(code)?.name || code)
                .filter(Boolean);

            return {
                label: `Phòng ${index + 1}`,
                value: row.room_type,
                displayValue: `${roomLabel} x${row.count} · ${templateLabel}${extraLabels.length > 0 ? ` · Add thêm: ${extraLabels.join(', ')}` : ''}`,
            };
        });
        const designItem = customState.design_options.length > 0
            ? [{
                label: 'Lựa chọn thiết kế',
                value: customState.design_options,
                displayValue: customState.design_options.map((code) => itemByCode.get(code)?.name || code).join(', '),
            }]
            : [];
        const addonItem = customState.special_addons.length > 0
            ? [{
                label: 'Add-on đặc biệt',
                value: customState.special_addons,
                displayValue: customState.special_addons.map((code) => itemByCode.get(code)?.name || code).join(', '),
            }]
            : [];

        return [...baseItems, ...roomItems, ...designItem, ...addonItem];
    };

    const buildGuideItems = (levelCode = activeLevelCode()) => {
        const levelInputMap = buildLevelInputMap(levelCode);

        return visibleRows(levelCode, levelInputMap)
            .map((row) => {
                const key = row.dataset.fieldKey || '';
                const field = getFieldMeta(key);
                const guideContent = `${field?.guide_content || ''}`.trim();

                if (key === '' || guideContent === '') {
                    return null;
                }

                return {
                    key,
                    groupLabel: fieldGroupLabel(key),
                    fieldLabel: `${field?.label || key}`.trim(),
                    guideTitle: `${field?.guide_title || field?.label || key}`.trim(),
                    guideContent,
                    options: getFieldOptions(field)
                        .map((option) => `${option?.label || ''}`.trim())
                        .filter(Boolean),
                };
            })
            .filter(Boolean);
    };
    const catalogNodeLabel = (code) => nodeByCode.get(code)?.name || code || 'Khác';
    const catalogItemMeta = (item) => {
        const segments = [];

        if (item?.component_node_code) {
            segments.push(catalogNodeLabel(item.component_node_code));
        }

        if (item?.pricing_mode) {
            segments.push(pricingModeLabels[item.pricing_mode] || item.pricing_mode);
        }

        if (item?.unit) {
            segments.push(item.unit);
        }

        return segments.join(' · ');
    };
    const renderSelectionOptions = (target, items, selectedCodes, inputName, emptyMessage) => {
        if (!target) {
            return;
        }

        target.innerHTML = items.length > 0
            ? items.map((item) => `
                <label class="flex items-start gap-3 rounded-2xl border border-[#d7e3f4] bg-white px-4 py-4 transition hover:border-[#0d6cb6]">
                    <input
                        type="checkbox"
                        data-estimate-custom-input
                        data-input-group="${escapeHtml(inputName)}"
                        value="${escapeHtml(item.code)}"
                        ${selectedCodes.includes(item.code) ? 'checked' : ''}
                        class="mt-1 size-4 rounded border-[#99c5e7] text-[#0d6cb6] focus:ring-[#0d6cb6]"
                    >
                    <span class="min-w-0">
                        <span class="block font-semibold text-[#0d2a4d]">${escapeHtml(item.name)}</span>
                        <span class="mt-1 block text-sm leading-6 text-[#587087]">${escapeHtml(catalogItemMeta(item))}</span>
                    </span>
                </label>
            `).join('')
            : `
                <div class="rounded-2xl border border-dashed border-[#cfe3f5] bg-white px-4 py-4 text-sm leading-7 text-[#587087]">
                    ${escapeHtml(emptyMessage)}
                </div>
            `;
    };
    const renderRoomProgramSection = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => {
        if (!roomProgramSection || !roomProgramList) {
            return;
        }

        const buildingType = currentBuildingType(levelCode, inputMap);
        const visible = parseLevelList(roomProgramSection.dataset.visibleLevels).includes(levelCode);
        const roomTypeOptions = compatibleRoomTypes(levelCode, buildingType);

        roomProgramSection.classList.toggle('hidden', !visible);

        if (!visible) {
            return;
        }

        if (roomProgramAddButton) {
            roomProgramAddButton.disabled = roomTypeOptions.length === 0;
            roomProgramAddButton.classList.toggle('opacity-60', roomTypeOptions.length === 0);
            roomProgramAddButton.classList.toggle('cursor-not-allowed', roomTypeOptions.length === 0);
        }

        roomProgramEmpty?.classList.toggle('hidden', roomProgramState.length > 0);
        roomProgramList.innerHTML = roomProgramState.map((row, index) => {
            const templateOptions = compatibleTemplates(row.room_type, levelCode, buildingType);
            const extraOptions = compatibleRoomExtraItems(row.room_type, levelCode, buildingType);
            const template = roomTemplateByCode.get(row.template);
            const templateItems = Array.isArray(template?.template_items) ? template.template_items : [];

            return `
                <article class="rounded-[1.5rem] border border-[#d7e3f4] bg-white px-4 py-4">
                    <div class="flex flex-col gap-3 border-b border-[#e6eef7] pb-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#0d6cb6]">Dòng công năng ${index + 1}</p>
                            <p class="mt-2 text-sm leading-7 text-[#587087]">Chọn loại phòng, số lượng, mẫu nội thất chuẩn và các hạng mục add thêm nếu có.</p>
                        </div>
                        <button
                            type="button"
                            data-room-program-action="remove"
                            data-row-index="${index}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#f5c2c7] bg-[#fff5f5] px-4 py-2 text-sm font-semibold text-[#b42318] transition hover:border-[#b42318]"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                            Xóa dòng
                        </button>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-[#0d2a4d]">Loại phòng</span>
                            <select data-estimate-custom-input data-input-group="room_program" data-row-index="${index}" data-field="room_type" class="w-full rounded-2xl border border-[#b7d8f1] bg-white px-4 py-3 text-sm text-[#0d2a4d] outline-none transition focus:border-[#0d6cb6]">
                                <option value="">Chọn loại phòng</option>
                                ${roomTypeOptions.map((item) => `<option value="${escapeHtml(item.code)}" ${item.code === row.room_type ? 'selected' : ''}>${escapeHtml(item.label || item.code)}</option>`).join('')}
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-[#0d2a4d]">Số lượng phòng</span>
                            <input type="number" min="1" step="1" value="${escapeHtml(`${row.count || 1}`)}" data-estimate-custom-input data-input-group="room_program" data-row-index="${index}" data-field="count" class="w-full rounded-2xl border border-[#b7d8f1] bg-white px-4 py-3 text-sm text-[#0d2a4d] outline-none transition focus:border-[#0d6cb6]">
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-[#0d2a4d]">Mẫu nội thất chuẩn</span>
                            <select data-estimate-custom-input data-input-group="room_program" data-row-index="${index}" data-field="template" class="w-full rounded-2xl border border-[#b7d8f1] bg-white px-4 py-3 text-sm text-[#0d2a4d] outline-none transition focus:border-[#0d6cb6]" ${row.room_type === '' ? 'disabled' : ''}>
                                <option value="">Chọn template</option>
                                ${templateOptions.map((item) => `<option value="${escapeHtml(item.code)}" ${item.code === row.template ? 'selected' : ''}>${escapeHtml(item.name)}</option>`).join('')}
                            </select>
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[0.92fr_1.08fr]">
                        <div class="rounded-2xl border border-[#e6eef7] bg-[#fbfdff] px-4 py-4">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#0d6cb6]">Preset chuẩn theo template</p>
                            <div class="mt-3 text-sm leading-7 text-[#587087]">
                                ${template
                                    ? `
                                        <p class="font-semibold text-[#0d2a4d]">${escapeHtml(template.name)}</p>
                                        ${template.description ? `<p class="mt-1">${escapeHtml(template.description)}</p>` : ''}
                                        <ul class="mt-3 space-y-2">
                                            ${templateItems.length > 0
                                                ? templateItems.map((item) => `
                                                    <li class="rounded-2xl border border-[#d7e3f4] bg-white px-3 py-2">
                                                        <span class="font-semibold text-[#0d2a4d]">${escapeHtml(item.catalog_item_label || item.catalog_item_code || 'Hạng mục')}</span>
                                                        <span class="mt-1 block text-xs uppercase tracking-[0.12em] text-[#587087]">${escapeHtml(item.default_quantity_formula || '1')}</span>
                                                    </li>
                                                `).join('')
                                                : '<li class="rounded-2xl border border-dashed border-[#d7e3f4] bg-white px-3 py-3 text-[#587087]">Template này chưa có item mặc định.</li>'}
                                        </ul>
                                    `
                                    : '<div class="rounded-2xl border border-dashed border-[#d7e3f4] bg-white px-3 py-3 text-[#587087]">Chọn một template để xem danh sách nội thất chuẩn theo quy ước hiện tại.</div>'}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-[#e6eef7] bg-[#fbfdff] px-4 py-4">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#0d6cb6]">Nội thất add thêm ngoài preset</p>
                            <div class="mt-3 grid gap-2 md:grid-cols-2">
                                ${extraOptions.length > 0
                                    ? extraOptions.map((item) => `
                                        <label class="flex items-start gap-3 rounded-2xl border border-[#d7e3f4] bg-white px-3 py-3 transition hover:border-[#0d6cb6]">
                                            <input
                                                type="checkbox"
                                                data-estimate-custom-input
                                                data-input-group="room_program"
                                                data-row-index="${index}"
                                                data-field="extra_item"
                                                value="${escapeHtml(item.code)}"
                                                ${row.extra_items.includes(item.code) ? 'checked' : ''}
                                                class="mt-1 size-4 rounded border-[#99c5e7] text-[#0d6cb6] focus:ring-[#0d6cb6]"
                                            >
                                            <span class="min-w-0">
                                                <span class="block font-semibold text-[#0d2a4d]">${escapeHtml(item.name)}</span>
                                                <span class="mt-1 block text-xs uppercase tracking-[0.12em] text-[#587087]">${escapeHtml(catalogItemMeta(item))}</span>
                                            </span>
                                        </label>
                                    `).join('')
                                    : '<div class="rounded-2xl border border-dashed border-[#d7e3f4] bg-white px-3 py-3 text-sm leading-7 text-[#587087] md:col-span-2">Chưa có add-on phù hợp cho loại phòng hoặc loại công trình đang chọn.</div>'}
                            </div>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    };
    const renderCustomSections = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => {
        const buildingType = currentBuildingType(levelCode, inputMap);
        const customState = sanitizeRoomProgramState(levelCode, inputMap);

        renderRoomProgramSection(levelCode, inputMap);

        if (designOptionsSection) {
            const visible = parseLevelList(designOptionsSection.dataset.visibleLevels).includes(levelCode);
            designOptionsSection.classList.toggle('hidden', !visible);

            if (visible) {
                renderSelectionOptions(
                    designOptionsList,
                    compatibleDesignItems(levelCode, buildingType),
                    customState.design_options,
                    'design_options',
                    'Chưa có lựa chọn thiết kế phù hợp với loại công trình đang chọn.',
                );
            }
        }

        if (specialAddonsSection) {
            const visible = parseLevelList(specialAddonsSection.dataset.visibleLevels).includes(levelCode);
            specialAddonsSection.classList.toggle('hidden', !visible);

            if (visible) {
                renderSelectionOptions(
                    specialAddonsList,
                    compatibleSpecialAddons(levelCode, buildingType),
                    customState.special_addons,
                    'special_addons',
                    'Chưa có add-on đặc biệt phù hợp với loại công trình hoặc level hiện tại.',
                );
            }
        }
    };

    const showAlert = (messages) => {
        if (!formAlert) {
            return;
        }

        const items = Array.isArray(messages) ? messages.filter(Boolean) : [`${messages}`];

        if (items.length === 0) {
            formAlert.classList.add('hidden');
            formAlert.textContent = '';

            return;
        }

        formAlert.classList.remove('hidden');
        formAlert.innerHTML = items.map((message) => `<p>${escapeHtml(message)}</p>`).join('');
    };

    const validateInputs = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => {
        const errors = [];
        const levelInputMap = buildLevelInputMap(levelCode);
        const customState = sanitizeRoomProgramState(levelCode, inputMap);

        visibleRows(levelCode, levelInputMap).forEach((row) => {
            const key = row.dataset.fieldKey || '';
            const field = getFieldMeta(key);
            const label = field.label || key;
            const type = `${field?.type || row.dataset.fieldType || 'text'}`;
            const requiredLevels = Array.isArray(field?.required_levels) ? field.required_levels : parseLevelList(row.dataset.requiredLevels);
            const value = inputMap[key];

            if (requiredLevels.includes(levelCode) && isEmptyValue(value)) {
                errors.push(`Vui lòng nhập ${label}.`);
                return;
            }

            if (isEmptyValue(value)) {
                return;
            }

            if ((type === 'number' || type === 'integer') && !Number.isFinite(Number(value))) {
                errors.push(`${label} phải là số hợp lệ.`);
            }
        });

        if (inputMap.has_terrace === true) {
            const terraceRooftopArea = terraceRooftopAreaFromMap(inputMap);
            const tumArea = Number(inputMap.tum_area || 0);
            const terraceArea = Number(inputMap.terrace_area || 0);

            if (Number.isFinite(terraceRooftopArea) && terraceRooftopArea > 0 && Math.abs((tumArea + terraceArea) - terraceRooftopArea) > 0.01) {
                errors.push('Tổng diện tích tum và sân thượng phải bằng diện tích sân thượng quy đổi (125% diện tích sàn xây dựng).');
            }
        }

        if (inputMap.has_pool === true && (!Number.isFinite(Number(inputMap.pool_area)) || Number(inputMap.pool_area) <= 0)) {
            errors.push('Khi có hồ bơi, vui lòng nhập diện tích hồ bơi lớn hơn 0.');
        }

        customState.room_program.forEach((row, index) => {
            if (!row.room_type) {
                errors.push(`Dòng công năng ${index + 1} chưa chọn loại phòng.`);
            }

            if (!Number.isFinite(Number(row.count)) || Number(row.count) <= 0) {
                errors.push(`Số lượng phòng ở dòng công năng ${index + 1} phải lớn hơn 0.`);
            }
        });

        return Array.from(new Set(errors));
    };

    const proxyScope = (context) => new Proxy(context, {
        has: () => true,
        get(target, property) {
            if (Object.prototype.hasOwnProperty.call(target, property)) {
                return target[property];
            }

            if (property in globalThis) {
                return globalThis[property];
            }

            return undefined;
        },
    });

    const evaluateExpression = (expression, context) => {
        if (expression === null || expression === undefined || expression === '') {
            return null;
        }

        if (typeof expression === 'number') {
            return expression;
        }

        try {
            return Function('scope', `with (scope) { return (${expression}); }`)(proxyScope(context));
        } catch (error) {
            return null;
        }
    };

    const componentNotes = (definition, context) => {
        const notes = [];

        if (definition.option_field) {
            const optionField = getFieldMeta(definition.option_field);
            const optionValue = context[definition.option_field];

            if (!isEmptyValue(optionValue)) {
                notes.push(displayValue(optionField, optionValue));
            }
        }

        if (definition.key === 'basement_small_area_surcharge') {
            notes.push('Áp dụng phụ hệ số hầm nhỏ dưới 70m2');
        }

        if (definition.key === 'tum' && context.has_terrace === true) {
            notes.push('Thuộc cụm tum - sân thượng');
        }

        if (definition.key === 'terrace') {
            const terraceField = getFieldMeta('terrace_cover_type');

            if (!isEmptyValue(context.terrace_cover_type)) {
                notes.push(displayValue(terraceField, context.terrace_cover_type));
            }

            if (context.has_terrace === true) {
                notes.push('Tổng Tum + ST = 125% sàn');
            }
        }

        if (definition.key === 'roof' && context.has_terrace === true) {
            notes.push('Mái che tính theo phần Tum trong cụm sân thượng');
        }

        return notes.filter(Boolean);
    };

    const coefficientForComponent = (definition, context) => {
        if (typeof definition.coefficient === 'number') {
            return definition.coefficient;
        }

        if (definition.coefficient_from_option && definition.option_field) {
            return Number(definition.coefficient_from_option?.[context[definition.option_field]] || 0);
        }

        if (definition.coefficient_from_expression) {
            return Number(evaluateExpression(definition.coefficient_from_expression, context) || 0);
        }

        return 0;
    };

    const createComponent = (definition, context, overrides = {}) => {
        const area = roundArea(overrides.area ?? evaluateExpression(definition.area, context));
        const coefficient = roundArea(overrides.coefficient ?? coefficientForComponent(definition, context));
        const convertedArea = roundArea(area * coefficient);

        if (!Number.isFinite(area) || area <= 0) {
            return null;
        }

        return {
            key: overrides.key || definition.key,
            section: overrides.section || definition.section,
            label: overrides.label || definition.label,
            area,
            coefficient,
            converted_area: convertedArea,
            notes: overrides.notes || componentNotes(definition, context),
        };
    };

    const engineContext = (levelCode, inputMap) => {
        const context = {
            level: levelCode,
            apply_small_basement_surcharge: true,
            ...inputMap,
        };

        Object.values(estimatorUi?.fields || {}).forEach((field) => {
            if (!field?.key || !isEmptyValue(context[field.key])) {
                return;
            }

            if (field.type === 'boolean') {
                context[field.key] = false;
                return;
            }

            if (getFieldOptions(field).some((option) => `${option.value}` === 'none')) {
                context[field.key] = 'none';
            }
        });

        return context;
    };

    const suggestedPackage = (packageKey, finishPackage) => {
        const normalizedFinish = `${finishPackage || ''}`.toLowerCase();
        const normalizedKey = `${packageKey || ''}`.toLowerCase();

        if (normalizedFinish === 'premium') {
            return normalizedKey.includes('premium') || normalizedKey.includes('high');
        }

        if (normalizedFinish === 'standard_plus') {
            return normalizedKey.includes('high') || normalizedKey.includes('standard');
        }

        if (normalizedFinish === 'standard') {
            return normalizedKey.includes('standard');
        }

        return false;
    };
    const recommendedPackage = (packages = []) => packages.find((item) => item.suggested && `${item.key || ''}`.includes('construction'))
        || packages.find((item) => `${item.key || ''}`.includes('construction'))
        || packages.find((item) => item.suggested)
        || packages[0]
        || null;
    const resolvePriceBook = (levelCode = activeLevelCode(), inputMap = buildInputMap(levelCode)) => {
        const preferredCode = normalizeCodeValue(inputMap.price_set) || (levelCode === 'level_3' ? 'internal-2026-v1' : 'advanced-2026-v1');
        const buildingType = currentBuildingType(levelCode, inputMap);
        const eligibleBooks = priceBooks.filter((book) => supportsLevel(book, levelCode) && matchesBuildingType(book, buildingType));

        return eligibleBooks.find((book) => book.code === preferredCode) || eligibleBooks[0] || null;
    };
    const evaluateQuantityFormula = (formula, context, fallback = 0) => {
        const resolved = Number(evaluateExpression(formula || `${fallback}`, context) ?? fallback);

        return Number.isFinite(resolved) ? roundArea(Math.max(0, resolved)) : 0;
    };
    const calculateLineSubtotal = (item, quantity, unitPrice, baseAmount = 0) => {
        if (!Number.isFinite(quantity) || quantity <= 0) {
            return 0;
        }

        if (`${item?.pricing_mode || ''}` === 'percent_base') {
            return Math.round(Number(baseAmount || 0) * (Number(unitPrice || 0) / 100));
        }

        if (`${item?.pricing_mode || ''}` === 'fixed') {
            return Math.round(Number(unitPrice || 0) * Math.max(1, Number(quantity || 0)));
        }

        return Math.round(Number(unitPrice || 0) * Number(quantity || 0));
    };
    const buildPricedLine = (item, context, priceBook, baseAmount, overrides = {}) => {
        if (!item) {
            return null;
        }

        const quantity = overrides.quantity ?? evaluateQuantityFormula(
            overrides.quantity_formula || item.default_quantity_formula,
            context,
            0,
        );

        if (!Number.isFinite(quantity) || quantity <= 0) {
            return null;
        }

        const unitPrice = Number(priceBook?.item_prices?.[item.code] || 0);
        const subtotal = calculateLineSubtotal(item, quantity, unitPrice, baseAmount);

        return {
            code: item.code,
            label: item.name,
            item_type: item.item_type,
            component_node_code: item.component_node_code,
            component_node_label: catalogNodeLabel(item.component_node_code),
            unit: item.unit,
            pricing_mode: item.pricing_mode,
            quantity,
            unit_price: unitPrice,
            subtotal,
            price_book_code: priceBook?.code || null,
            pricing_status: unitPrice > 0 ? 'priced' : 'missing_price',
            source_key: overrides.source_key || 'custom',
            source_label: overrides.source_label || item.name,
            room_type: overrides.room_type || null,
            room_type_label: overrides.room_type_label || null,
            template_code: overrides.template_code || null,
            template_label: overrides.template_label || null,
        };
    };
    const buildComponentTreeBreakdown = (lines = []) => {
        const accumulator = new Map();
        const touchNode = (code) => {
            if (!code || accumulator.has(code)) {
                return accumulator.get(code) || null;
            }

            const node = nodeByCode.get(code);

            if (!node) {
                return null;
            }

            const bucket = {
                code: node.code,
                name: node.name,
                node_type: node.node_type,
                parent_code: node.parent_code,
                subtotal: 0,
                items: [],
            };

            accumulator.set(code, bucket);

            return bucket;
        };

        lines.forEach((line) => {
            const leafNode = touchNode(line.component_node_code);

            if (leafNode) {
                leafNode.items.push(line);
            }

            let currentCode = line.component_node_code;

            while (currentCode) {
                const bucket = touchNode(currentCode);

                if (!bucket) {
                    break;
                }

                bucket.subtotal = Math.round(Number(bucket.subtotal || 0) + Number(line.subtotal || 0));
                currentCode = bucket.parent_code || null;
            }
        });

        const buildTree = (nodes = []) => (Array.isArray(nodes) ? nodes : [])
            .map((node) => {
                const bucket = accumulator.get(node.code);
                const children = buildTree(node.children || []);
                const subtotal = Number(bucket?.subtotal || 0);

                if (subtotal <= 0 && children.length === 0) {
                    return null;
                }

                return {
                    code: node.code,
                    name: node.name,
                    node_type: node.node_type,
                    subtotal,
                    items: bucket?.items || [],
                    children,
                };
            })
            .filter(Boolean);

        return buildTree(catalogTree);
    };

    const calculateEstimate = (levelCode = activeLevelCode()) => {
        const inputMap = buildInputMap(levelCode);

        if (inputMap.has_elevator === true && !Number.isFinite(Number(inputMap.elevator_service_floors))) {
            inputMap.elevator_service_floors = Math.max(1, Math.trunc(Number(inputMap.floors || 1)));
        }

        if (inputMap.has_pool === true && normalizeCodeValue(inputMap.pool_type) === '') {
            inputMap.pool_type = 'skimmer';
        }

        const baseContext = engineContext(levelCode, inputMap);
        const derived = {};

        Object.entries(estimatorUi?.formula?.derived || {}).forEach(([key, expression]) => {
            derived[key] = roundArea(evaluateExpression(expression, {
                ...baseContext,
                ...derived,
            }));
        });

        const context = {
            ...baseContext,
            ...derived,
        };

        const components = [];

        (estimatorUi?.formula?.components || []).forEach((definition) => {
            const matchesCondition = definition.condition
                ? Boolean(evaluateExpression(definition.condition, context))
                : true;

            if (!matchesCondition) {
                return;
            }

            if (definition.key === 'upper_floors') {
                const floors = Math.max(0, Math.round(Number(context.typical_floor_count ?? (Number(context.floors || 0) - 1))));

                for (let index = 0; index < floors; index += 1) {
                    const component = createComponent(definition, context, {
                        key: `${definition.key}_${index + 1}`,
                        label: `Tầng ${index + 2} (Lầu ${index + 1})`,
                        area: context.base_area,
                        notes: [],
                    });

                    if (component) {
                        components.push(component);
                    }
                }

                return;
            }

            const component = createComponent(definition, context, definition.key === 'mezzanine_void'
                ? { label: 'Thông tầng của lửng' }
                : {});

            if (component) {
                components.push(component);
            }
        });

        const convertedArea = roundArea(components.reduce((total, component) => total + Number(component.converted_area || 0), 0));
        const basePricingPackages = (estimatorUi?.pricing?.packages || []).map((item) => {
            const unitPrice = Number(item.unit_price || 0);

            return {
                key: item.key,
                label: item.label,
                currency: item.currency || 'VND',
                unit_price: unitPrice,
                total_converted_area: convertedArea,
                amount: Math.round(convertedArea * unitPrice),
                suggested: suggestedPackage(item.key, context.finish_package),
            };
        });
        const baseReferenceAmount = recommendedPackage(basePricingPackages)?.amount || 0;
        const priceBook = resolvePriceBook(levelCode, inputMap);
        const groupedInput = levelCode === 'level_1'
            ? {
                room_program: [],
                design_options: [],
                special_addons: [],
            }
            : sanitizeRoomProgramState(levelCode, inputMap);
        const detailedContext = {
            ...context,
            converted_area: convertedArea,
        };
        const roomBreakdown = groupedInput.room_program.map((row) => {
            const roomTypeLabel = roomTypeByCode.get(row.room_type)?.label || row.room_type;
            const template = roomTemplateByCode.get(row.template) || compatibleTemplates(row.room_type, levelCode, currentBuildingType(levelCode, inputMap))[0] || null;
            const rowContext = {
                ...detailedContext,
                room_count: Number(row.count || 0),
            };
            const presetLines = (Array.isArray(template?.template_items) ? template.template_items : [])
                .map((templateItem) => buildPricedLine(
                    itemByCode.get(templateItem.catalog_item_code),
                    rowContext,
                    priceBook,
                    baseReferenceAmount,
                    {
                        quantity_formula: templateItem.default_quantity_formula,
                        source_key: 'room_program',
                        source_label: `${roomTypeLabel} · preset`,
                        room_type: row.room_type,
                        room_type_label: roomTypeLabel,
                        template_code: template?.code || null,
                        template_label: template?.name || null,
                    },
                ))
                .filter(Boolean);
            const extraLines = row.extra_items
                .map((code) => buildPricedLine(
                    itemByCode.get(code),
                    rowContext,
                    priceBook,
                    baseReferenceAmount,
                    {
                        source_key: 'room_program',
                        source_label: `${roomTypeLabel} · add thêm`,
                        room_type: row.room_type,
                        room_type_label: roomTypeLabel,
                        template_code: template?.code || null,
                        template_label: template?.name || null,
                    },
                ))
                .filter(Boolean);
            const items = [...presetLines, ...extraLines];

            return {
                room_type: row.room_type,
                room_type_label: roomTypeLabel,
                count: Number(row.count || 0),
                template_code: template?.code || null,
                template_label: template?.name || null,
                items,
                subtotal: Math.round(items.reduce((total, item) => total + Number(item.subtotal || 0), 0)),
            };
        }).filter((row) => row.items.length > 0 || row.count > 0);
        const roomLines = roomBreakdown.flatMap((row) => row.items);
        const designLines = groupedInput.design_options
            .map((code) => buildPricedLine(
                itemByCode.get(code),
                detailedContext,
                priceBook,
                baseReferenceAmount,
                {
                    source_key: 'design_options',
                    source_label: 'Thiết kế',
                },
            ))
            .filter(Boolean);
        const specialLines = groupedInput.special_addons
            .map((code) => buildPricedLine(
                itemByCode.get(code),
                detailedContext,
                priceBook,
                baseReferenceAmount,
                {
                    source_key: 'special_addons',
                    source_label: 'Add-on đặc biệt',
                },
            ))
            .filter(Boolean);
        const technicalLines = [];

        if (inputMap.has_elevator === true) {
            const elevatorCode = 'elevator_standard';
            const elevatorItem = itemByCode.get(elevatorCode);
            const elevatorLine = buildPricedLine(
                elevatorItem,
                {
                    ...detailedContext,
                    elevator_service_floors: Number(inputMap.elevator_service_floors || inputMap.floors || 0),
                },
                priceBook,
                baseReferenceAmount,
                {
                    source_key: 'technical_inputs',
                    source_label: 'Thang máy',
                },
            );

            if (elevatorLine) {
                technicalLines.push(elevatorLine);
            }
        }

        if (inputMap.has_pool === true && Number(inputMap.pool_area || 0) > 0) {
            const poolCode = ({
                overflow: 'pool_overflow',
                jacuzzi: 'pool_jacuzzi',
                skimmer: 'pool_standard',
            })[normalizeCodeValue(inputMap.pool_type)] || 'pool_standard';
            const poolItem = itemByCode.get(poolCode);
            const poolLine = buildPricedLine(
                poolItem,
                {
                    ...detailedContext,
                    pool_area: Number(inputMap.pool_area || 0),
                },
                priceBook,
                baseReferenceAmount,
                {
                    source_key: 'technical_inputs',
                    source_label: 'Hồ bơi',
                },
            );

            if (poolLine) {
                technicalLines.push(poolLine);
            }
        }

        const detailedLines = [...roomLines, ...designLines, ...specialLines, ...technicalLines];
        const overlaySubtotal = Math.round(detailedLines.reduce((total, item) => total + Number(item.subtotal || 0), 0));
        const roomSubtotal = Math.round(roomLines.reduce((total, item) => total + Number(item.subtotal || 0), 0));
        const designSubtotal = Math.round(designLines.reduce((total, item) => total + Number(item.subtotal || 0), 0));
        const specialSubtotal = Math.round(specialLines.reduce((total, item) => total + Number(item.subtotal || 0), 0));
        const technicalSubtotal = Math.round(technicalLines.reduce((total, item) => total + Number(item.subtotal || 0), 0));
        const discountPercent = Number(inputMap.discount_percent || 0);
        const surchargePercent = Number(inputMap.surcharge_percent || 0);
        const vatMode = normalizeCodeValue(inputMap.vat_mode) || 'exclude';
        const pricingPackages = basePricingPackages.map((item) => {
            const subtotalBeforeControls = Math.round(Number(item.amount || 0) + overlaySubtotal);
            const surchargeAmount = Math.round(subtotalBeforeControls * Math.max(0, surchargePercent) / 100);
            const discountAmount = Math.round(subtotalBeforeControls * Math.max(0, discountPercent) / 100);
            const subtotalAfterControls = subtotalBeforeControls + surchargeAmount - discountAmount;
            const vatAmount = vatMode === 'include' ? Math.round(subtotalAfterControls * 0.1) : 0;

            return {
                ...item,
                base_amount: Math.round(Number(item.amount || 0)),
                overlay_amount: overlaySubtotal,
                surcharge_amount: surchargeAmount,
                discount_amount: discountAmount,
                vat_amount: vatAmount,
                subtotal_before_controls: subtotalBeforeControls,
                amount: subtotalAfterControls + vatAmount,
            };
        });
        const pricingSummary = {
            price_book_code: priceBook?.code || null,
            price_book_name: priceBook?.name || null,
            price_book_version: priceBook?.version || null,
            room_subtotal: roomSubtotal,
            design_subtotal: designSubtotal,
            special_addons_subtotal: specialSubtotal,
            technical_subtotal: technicalSubtotal,
            overlay_subtotal: overlaySubtotal,
            discount_percent: discountPercent,
            surcharge_percent: surchargePercent,
            vat_mode: vatMode,
            packages: pricingPackages,
            recommended_package: recommendedPackage(pricingPackages),
        };

        return {
            level: levelCode,
            formula_version: estimatorUi?.formula?.meta?.version || 'v1',
            price_version: priceBook?.version || estimatorUi?.pricing?.meta?.version || 'v1',
            catalog_version: estimatorUi?.catalog_version || 'seed-v1',
            room_template_version: estimatorUi?.room_template_version || 'seed-v1',
            input: inputMap,
            input_groups: levelCode === 'level_1' ? null : buildGroupedPayload(levelCode, inputMap),
            derived,
            components,
            converted_area_breakdown: {
                rows: components,
                total_converted_area: convertedArea,
                total_base_area: roundArea(derived.base_area || 0),
            },
            component_tree_breakdown: buildComponentTreeBreakdown(detailedLines),
            room_breakdown: roomBreakdown,
            totals: {
                converted_area: convertedArea,
                overlay_amount: overlaySubtotal,
            },
            pricing: {
                packages: pricingPackages,
            },
            pricing_summary: pricingSummary,
        };
    };

    const renderSummary = (items) => {
        if (!summaryList) {
            return;
        }

        summaryList.innerHTML = items.length > 0
            ? items.map((item) => `
                <li class="flex items-start justify-between gap-3 py-3">
                    <span class="font-semibold text-[#0d2a4d]">${escapeHtml(item.label)}</span>
                    <span class="text-right">${escapeHtml(item.displayValue)}</span>
                </li>
            `).join('')
            : '';
    };

    const renderBreakdown = (result) => {
        if (!breakdownRows) {
            return;
        }

        const rows = Array.isArray(result?.converted_area_breakdown?.rows)
            ? result.converted_area_breakdown.rows
            : (Array.isArray(result?.components) ? result.components : []);
        const terraceSummaryArea = roundArea(result?.derived?.terrace_rooftop_area || 0);
        const hasAutoTerrace = result?.input?.has_terrace === true && terraceSummaryArea > 0;
        const terraceSummaryRow = hasAutoTerrace
            ? `
                <tr class="bg-[#eef7ff]">
                    <td class="px-4 py-4 font-bold text-[#0d4f8f]">Sàn sân thượng tự tính</td>
                    <td class="px-4 py-4 font-bold text-[#0d2a4d]">${escapeHtml(areaFormatter.format(terraceSummaryArea))}</td>
                    <td class="px-4 py-4 text-[#587087]">-</td>
                    <td class="px-4 py-4 text-[#587087]">-</td>
                    <td class="px-4 py-4 text-[#587087]">Hệ thống lấy 125% diện tích sàn làm cụm sân thượng quy đổi. Khách hàng có thể chỉnh tay miễn là tổng Tum + ST vẫn bằng giá trị này.</td>
                </tr>
            `
            : '';

        breakdownRows.innerHTML = rows.length > 0
            ? `${terraceSummaryRow}${rows.map((component) => `
                <tr>
                    <td class="px-4 py-4 font-semibold text-[#0d4f8f]">${escapeHtml(component.label)}</td>
                    <td class="px-4 py-4">${escapeHtml(areaFormatter.format(component.area))}</td>
                    <td class="px-4 py-4 font-semibold">${escapeHtml(formatPercent(component.coefficient))}</td>
                    <td class="px-4 py-4 font-bold text-[#0d2a4d]">${escapeHtml(areaFormatter.format(component.converted_area))}</td>
                    <td class="px-4 py-4 text-[#587087]">${escapeHtml((component.notes || []).join(' · ') || 'Theo cấu hình hiện tại')}</td>
                </tr>
            `).join('')}
                <tr class="bg-[#e7f3ff]">
                    <td colspan="3" class="px-4 py-4 text-right font-black uppercase tracking-[0.12em] text-[#0d4f8f]">Tổng cộng diện tích quy đổi</td>
                    <td class="px-4 py-4 font-black text-[#0d2a4d]">${escapeHtml(areaFormatter.format(result?.converted_area_breakdown?.total_converted_area || result?.totals?.converted_area || 0))}</td>
                    <td class="px-4 py-4 text-[#587087]">m2</td>
                </tr>`
            : `
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-[#587087]">
                        Nhập thông số công trình để breakdown chi tiết xuất hiện tại đây.
                    </td>
                </tr>
            `;
    };
    const renderComponentTreeBreakdown = (result) => {
        if (!componentTreeContainer) {
            return;
        }

        const renderNodes = (nodes = [], depth = 0) => nodes.map((node) => `
            <div class="rounded-2xl border border-[#d7e3f4] ${depth === 0 ? 'bg-[#fbfdff]' : 'bg-white'} px-4 py-4" style="margin-left:${depth * 14}px">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-[#0d2a4d]">${escapeHtml(node.name)}</p>
                        <p class="mt-1 text-xs uppercase tracking-[0.12em] text-[#587087]">${escapeHtml(node.node_type || 'component')}</p>
                    </div>
                    <p class="text-right text-sm font-black text-[#0d2a4d]">${escapeHtml(maskCurrency(node.subtotal || 0))}</p>
                </div>
                ${Array.isArray(node.items) && node.items.length > 0 ? `
                    <div class="mt-3 space-y-2">
                        ${node.items.map((item) => `
                            <div class="rounded-2xl border border-[#e6eef7] bg-white px-3 py-3 text-sm leading-6 text-[#587087]">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-[#0d2a4d]">${escapeHtml(item.label)}</p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.12em] text-[#587087]">${escapeHtml(`${areaFormatter.format(item.quantity || 0)} ${item.unit || ''} · ${pricingModeLabels[item.pricing_mode] || item.pricing_mode || ''}`.trim())}</p>
                                    </div>
                                    <p class="text-right font-bold text-[#0d2a4d]">${escapeHtml(maskCurrency(item.subtotal || 0))}</p>
                                </div>
                                ${item.pricing_status === 'missing_price' ? '<p class="mt-2 text-xs font-semibold uppercase tracking-[0.12em] text-[#b45309]">Chưa có đơn giá trong price book hiện tại</p>' : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                ${Array.isArray(node.children) && node.children.length > 0 ? `<div class="mt-3 space-y-3">${renderNodes(node.children, depth + 1).join('')}</div>` : ''}
            </div>
        `);
        const nodes = Array.isArray(result?.component_tree_breakdown) ? result.component_tree_breakdown : [];

        componentTreeContainer.innerHTML = nodes.length > 0
            ? renderNodes(nodes).join('')
            : `
                <div class="rounded-2xl border border-dashed border-[#cfe3f5] bg-[#fbfdff] px-4 py-4 text-sm leading-7 text-[#587087]">
                    Các hạng mục chi tiết sẽ xuất hiện khi bạn chọn phòng, template, thiết kế hoặc add-on đặc biệt.
                </div>
            `;
    };
    const renderRoomBreakdown = (result) => {
        if (!roomBreakdownContainer) {
            return;
        }

        const rooms = Array.isArray(result?.room_breakdown) ? result.room_breakdown : [];

        roomBreakdownContainer.innerHTML = rooms.length > 0
            ? rooms.map((room, index) => `
                <article class="rounded-2xl border border-[#d7e3f4] bg-[#fbfdff] px-4 py-4">
                    <div class="flex items-start justify-between gap-4 border-b border-[#e6eef7] pb-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#0d6cb6]">Phòng ${index + 1}</p>
                            <p class="mt-1 font-semibold text-[#0d2a4d]">${escapeHtml(room.room_type_label || room.room_type || 'Phòng')}</p>
                            <p class="mt-1 text-sm leading-6 text-[#587087]">${escapeHtml(`Số lượng: ${room.count || 0} · Template: ${room.template_label || 'Chưa chọn'}`)}</p>
                        </div>
                        <p class="text-right text-sm font-black text-[#0d2a4d]">${escapeHtml(maskCurrency(room.subtotal || 0))}</p>
                    </div>
                    <div class="mt-3 space-y-2">
                        ${Array.isArray(room.items) && room.items.length > 0
                            ? room.items.map((item) => `
                                <div class="rounded-2xl border border-[#e6eef7] bg-white px-3 py-3 text-sm leading-6 text-[#587087]">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-[#0d2a4d]">${escapeHtml(item.label)}</p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.12em] text-[#587087]">${escapeHtml(`${areaFormatter.format(item.quantity || 0)} ${item.unit || ''} · ${item.source_label || ''}`.trim())}</p>
                                        </div>
                                        <p class="text-right font-bold text-[#0d2a4d]">${escapeHtml(maskCurrency(item.subtotal || 0))}</p>
                                    </div>
                                    ${item.pricing_status === 'missing_price' ? '<p class="mt-2 text-xs font-semibold uppercase tracking-[0.12em] text-[#b45309]">Chưa có đơn giá trong price book hiện tại</p>' : ''}
                                </div>
                            `).join('')
                            : '<div class="rounded-2xl border border-dashed border-[#d7e3f4] bg-white px-3 py-3 text-[#587087]">Template này chưa có item định giá.</div>'}
                    </div>
                </article>
            `).join('')
            : `
                <div class="rounded-2xl border border-dashed border-[#cfe3f5] bg-[#fbfdff] px-4 py-4 text-sm leading-7 text-[#587087]">
                    Preset phòng và nội thất add thêm sẽ hiển thị tại đây khi bạn khai báo room program.
                </div>
            `;
    };

    const renderPricingCards = (result) => {
        if (!pricingCards) {
            return;
        }

        const packages = result?.pricing_summary?.packages || result?.pricing?.packages || [];
        const overlayAmount = Number(result?.pricing_summary?.overlay_subtotal || 0);
        const priceBookName = `${result?.pricing_summary?.price_book_name || ''}`.trim();

        pricingCards.innerHTML = packages.length > 0
            ? packages.map((item) => `
                <div class="rounded-[1.35rem] border px-4 py-4 ${item.suggested ? 'border-[#0d6cb6] bg-[#eff7ff]' : 'border-[#d7e3f4] bg-[#fbfdff]'}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-bold text-[#0d2a4d]">${escapeHtml(item.label)}</p>
                            ${priceBookName ? `<p class="mt-1 text-xs uppercase tracking-[0.12em] text-[#587087]">${escapeHtml(priceBookName)}</p>` : ''}
                        </div>
                        ${item.suggested ? '<span class="inline-flex rounded-full bg-[#0d6cb6] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-white">Gợi ý</span>' : ''}
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-[#587087]">
                        <div class="flex items-center justify-between gap-4">
                            <p>Khung m2 quy đổi</p>
                            <p class="font-semibold text-[#0d2a4d]">${escapeHtml(maskCurrency(item.base_amount || item.amount || 0))}</p>
                        </div>
                        ${overlayAmount > 0 ? `
                            <div class="flex items-center justify-between gap-4">
                                <p>Overlay chi tiết</p>
                                <p class="font-semibold text-[#0d2a4d]">${escapeHtml(maskCurrency(item.overlay_amount || 0))}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="mt-4 flex items-end justify-between gap-4 border-t border-[#d7e3f4] pt-3">
                        <p class="text-sm text-[#587087]">Kết quả tạm tính</p>
                        <p class="text-lg font-black text-[#0d2a4d]">${escapeHtml(maskCurrency(item.amount))}</p>
                    </div>
                </div>
            `).join('')
            : '';
    };

    const renderPreviewPricing = (target, result) => {
        if (!target) {
            return;
        }

        const packages = result?.pricing_summary?.packages || result?.pricing?.packages || [];

        target.innerHTML = packages.length > 0
            ? packages.map((item) => `
                <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div>
                        <p class="font-semibold text-slate-900">${escapeHtml(item.label)}</p>
                        <p class="text-sm text-slate-500">${escapeHtml(result?.pricing_summary?.price_book_name || 'Số tiền đầy đủ sẽ được gửi trong email.')}</p>
                    </div>
                    <p class="text-right font-bold text-slate-900">${escapeHtml(maskCurrency(item.amount))}</p>
                </div>
            `).join('')
            : `
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-4 text-slate-500">
                    Khi hệ thống tính xong, phần bảng giá theo gói sẽ được chèn vào khối này để gửi cùng email khách hàng.
                </div>
            `;
    };
    const renderPreviewComponentBreakdown = (target, result) => {
        if (!target) {
            return;
        }

        const nodes = Array.isArray(result?.component_tree_breakdown) ? result.component_tree_breakdown : [];

        target.innerHTML = nodes.length > 0
            ? nodes.map((node) => `
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">${escapeHtml(node.name)}</p>
                            <p class="text-sm text-slate-500">${escapeHtml(`${(node.items || []).length} item${(node.children || []).length > 0 ? ` · ${(node.children || []).length} nhóm con` : ''}`)}</p>
                        </div>
                        <p class="text-right font-bold text-slate-900">${escapeHtml(maskCurrency(node.subtotal || 0))}</p>
                    </div>
                </div>
            `).join('')
            : `
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-4 text-slate-500">
                    Breakdown component tree sẽ tự xuất hiện khi có hạng mục chi tiết.
                </div>
            `;
    };
    const renderPreviewRoomBreakdown = (target, result) => {
        if (!target) {
            return;
        }

        const rooms = Array.isArray(result?.room_breakdown) ? result.room_breakdown : [];

        target.innerHTML = rooms.length > 0
            ? rooms.map((room) => `
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">${escapeHtml(room.room_type_label || room.room_type || 'Phòng')}</p>
                            <p class="text-sm text-slate-500">${escapeHtml(`x${room.count || 0} · ${room.template_label || 'Chưa chọn template'}`)}</p>
                        </div>
                        <p class="text-right font-bold text-slate-900">${escapeHtml(maskCurrency(room.subtotal || 0))}</p>
                    </div>
                </div>
            `).join('')
            : `
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-4 text-slate-500">
                    Preset phòng và nội thất add thêm sẽ hiển thị tại đây khi bạn khai báo công năng.
                </div>
            `;
    };

    const renderIllustration = (result) => {
        if (!illustrationScene) {
            return;
        }

        const components = Array.isArray(result?.components) ? result.components : [];
        const baseArea = roundArea(result?.derived?.base_area || 0);
        const terraceSummaryArea = roundArea(result?.derived?.terrace_rooftop_area || 0);
        const hasTerraceMode = result?.input?.has_terrace === true;
        const exactComponent = (key) => components.find((component) => `${component?.key || ''}` === key) || null;
        const componentSumByPrefix = (prefix) => roundArea(components
            .filter((component) => `${component?.key || ''}`.startsWith(prefix))
            .reduce((total, component) => total + Number(component?.converted_area || 0), 0));
        const upperFloorLayers = components
            .filter((component) => `${component?.key || ''}`.startsWith('upper_floors_'))
            .map((component) => ({
                label: component.label,
                area: roundArea(component.converted_area || component.area),
            }))
            .reverse();
        const mezzanineComponent = exactComponent('mezzanine');
        const mezzanineVoidComponent = exactComponent('mezzanine_void');
        const groundComponent = exactComponent('ground_floor');
        const roofComponent = exactComponent('roof');
        const foundationComponent = exactComponent('foundation');
        const basementArea = componentSumByPrefix('basement_');
        const tumArea = roundArea(exactComponent('tum')?.converted_area || exactComponent('tum')?.area || 0);
        const terraceArea = roundArea(exactComponent('terrace')?.converted_area || exactComponent('terrace')?.area || 0);
        const roofArea = hasTerraceMode
            ? roundArea(roofComponent?.converted_area || result?.derived?.roof_effective_area || baseArea)
            : roundArea(result?.derived?.roof_effective_area || roofComponent?.area || baseArea);
        const foundationArea = hasTerraceMode
            ? roundArea(foundationComponent?.converted_area || baseArea)
            : roundArea(baseArea);
        const basementDisplayArea = result?.input?.has_basement
            ? (hasTerraceMode
                ? basementArea
                : roundArea(result?.derived?.basement_effective_area || result?.input?.basement_area || baseArea))
            : 0;
        const layers = [];

        upperFloorLayers.forEach((layer) => layers.push({
            ...layer,
            area: hasTerraceMode
                ? roundArea(layer.area)
                : roundArea(components.find((component) => component.label === layer.label)?.area || layer.area),
        }));

        if (mezzanineComponent) {
            layers.push({
                label: 'Lửng',
                area: roundArea(hasTerraceMode ? (mezzanineComponent.converted_area || mezzanineComponent.area) : (mezzanineComponent.area || mezzanineComponent.converted_area)),
                note: mezzanineVoidComponent ? `Thông tầng: ${areaFormatter.format(roundArea(hasTerraceMode ? (mezzanineVoidComponent.converted_area || mezzanineVoidComponent.area) : (mezzanineVoidComponent.area || mezzanineVoidComponent.converted_area)))} m2` : '',
            });
        }

        layers.push({
            label: 'Tầng Trệt',
            area: roundArea(hasTerraceMode ? (groundComponent?.converted_area || baseArea) : (groundComponent?.area || baseArea)),
        });

        const layerHtml = layers.map((layer) => `
            <div class="relative mx-auto w-[74%] border-x-4 border-t-4 border-[#0d6cb6] bg-white px-3 py-3 text-center text-base font-black text-[#0d4f8f] ${layer.label === 'Tầng Trệt' ? 'border-b-4' : ''}">
                <div>${escapeHtml(layer.label)} - ${escapeHtml(areaFormatter.format(layer.area))} m2</div>
                ${layer.note ? `<div class="mt-1 text-sm font-semibold text-[#0d6cb6]">${escapeHtml(layer.note)}</div>` : ''}
                <span class="absolute -right-5 top-1/2 h-4 w-4 -translate-y-1/2 border-b-4 border-r-4 border-[#0d6cb6]"></span>
            </div>
        `).join('');

        const topFeatureItems = hasTerraceMode
            ? [
                tumArea > 0 ? { label: 'Tum', area: tumArea, align: 'left' } : null,
                terraceArea > 0 ? { label: 'ST', area: terraceArea, align: 'right' } : null,
            ].filter(Boolean)
            : [];

        const topFeatureLabels = hasTerraceMode && topFeatureItems.length > 0
            ? `
                <div class="relative min-h-[55px] border-4 border-[#0d6cb6] bg-white px-2 pb-2 pt-2 lg:min-h-[55px]">
                    <div class="grid h-full grid-cols-2 gap-0">
                        ${topFeatureItems.map((item) => item.label === 'Tum'
                            ? `
                                <div class="flex items-start">
                                    <div class="bg-white pr-2 text-left text-base font-black leading-none text-[#0d4f8f]">
                                        Tum= ${escapeHtml(areaFormatter.format(item.area))} m2
                                    </div>
                                </div>
                            `
                            : `
                                <div class="flex items-center justify-end">
                                    <div class="pl-2 text-right text-base font-black leading-none text-[#0d4f8f]">
                                        ${escapeHtml(item.label)}= ${escapeHtml(areaFormatter.format(item.area))} m2
                                    </div>
                                </div>
                            `).join('')}
                    </div>
                </div>
            `
            : '';

        const roofBlockHtml = hasTerraceMode
            ? `
                <div class="relative mx-auto mt-3 mb-4 w-[74%] overflow-visible">
                    <svg viewBox="0 0 300 90" class="absolute h-full w-[calc(100%+112px)] max-w-none" style="top:-25px;left:-110px;" aria-hidden="true">
                        <polyline points="60,74 150,14 240,74" fill="none" stroke="#d91016" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <line x1="86" y1="74" x2="214" y2="74" stroke="#0d6cb6" stroke-width="2.5" stroke-dasharray="7 6"></line>
                    </svg>
                    ${topFeatureLabels}
                </div>
            `
            : '';

        const terraceSummaryHtml = hasTerraceMode && terraceSummaryArea > 0
            ? `<p class="mb-3 text-center text-sm font-bold text-[#587087]">Sàn sân thượng tự tính (125% sàn): ${escapeHtml(areaFormatter.format(terraceSummaryArea))} m2</p>`
            : '';

        illustrationScene.innerHTML = `
            <div class="mx-auto flex max-w-[21rem] flex-col items-center">
                <p class="text-center text-2xl font-black text-[#0d2a4d]">Mái - ${escapeHtml(areaFormatter.format(roofArea))} m2</p>
                ${roofBlockHtml}
                ${terraceSummaryHtml}
                <div class="w-full">
                    <div class="mx-auto mb-1 h-2 w-[74%] bg-red-500"></div>
                    ${layerHtml}
                </div>
                ${basementDisplayArea > 0 ? `
                    <div class="mx-auto w-[82%] border-x-4 border-b-4 border-[#0d6cb6] bg-[#f4fbff] px-3 py-3 text-center text-base font-black text-[#0d4f8f]">
                        Hầm - ${escapeHtml(areaFormatter.format(basementDisplayArea))} m2
                    </div>
                ` : ''}
                <div class="mt-4 flex w-full items-end justify-center gap-16 text-[#0d6cb6]">
                    <div class="flex flex-col items-center">
                        <div class="h-6 w-1 bg-[#0d6cb6]"></div>
                        <div class="h-4 w-7 bg-[#0d6cb6]"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="h-6 w-1 bg-[#0d6cb6]"></div>
                        <div class="h-4 w-7 bg-[#0d6cb6]"></div>
                    </div>
                </div>
                <div class="mt-3 w-full border-t-4 border-[#0d6cb6]"></div>
                <p class="mt-4 text-center text-2xl font-black text-[#0d6cb6]">Móng - ${escapeHtml(areaFormatter.format(foundationArea))} m2</p>
            </div>
        `;
    };

    const renderPreviewSheets = (displayItems, result) => {
        const activeTab = selectedTab();
        const tierName = activeTab?.dataset.tierName || 'Chưa chọn';
        const requiresKey = activeTab?.dataset.requiresKey === 'true';
        const keyText = requiresKey
            ? (activeTab?.dataset.keyHelp || 'Cấp dự toán này yêu cầu mã key kích hoạt.')
            : 'Không yêu cầu mã key để gửi yêu cầu.';
        let bullets = [];

        try {
            bullets = JSON.parse(activeTab?.dataset.resultBullets || '[]');
        } catch (error) {
            bullets = [];
        }

        previewSheets.forEach((sheet) => {
            const previewTier = sheet.querySelector('[data-estimate-preview-tier]');
            const previewKeyStatus = sheet.querySelector('[data-estimate-preview-key-status]');
            const previewDelivery = sheet.querySelectorAll('[data-estimate-preview-delivery]');
            const previewResultTitle = sheet.querySelector('[data-estimate-preview-result-title]');
            const previewResultSummary = sheet.querySelector('[data-estimate-preview-result-summary]');
            const previewInputs = sheet.querySelector('[data-estimate-preview-inputs]');
            const previewBullets = sheet.querySelector('[data-estimate-preview-result-bullets]');
            const previewTotalArea = sheet.querySelector('[data-estimate-preview-total-area]');
            const previewBaseArea = sheet.querySelector('[data-estimate-preview-base-area]');
            const previewFormulaVersion = sheet.querySelector('[data-estimate-preview-formula-version]');
            const previewPricing = sheet.querySelector('[data-estimate-preview-pricing]');
            const previewComponentBreakdown = sheet.querySelector('[data-estimate-preview-component-breakdown]');
            const previewRoomBreakdown = sheet.querySelector('[data-estimate-preview-room-breakdown]');

            if (previewTier) {
                previewTier.textContent = tierName;
            }

            if (previewKeyStatus) {
                previewKeyStatus.textContent = keyText;
            }

            previewDelivery.forEach((node) => {
                node.textContent = activeTab?.dataset.deliveryText || '';
            });

            if (previewResultTitle) {
                previewResultTitle.textContent = activeTab?.dataset.resultTitle || 'Chọn một cấp dự toán để xem trước kết quả';
            }

            if (previewResultSummary) {
                previewResultSummary.textContent = activeTab?.dataset.resultSummary || '';
            }

            if (previewInputs) {
                previewInputs.innerHTML = displayItems.length > 0
                    ? displayItems.map((item) => `
                        <li class="flex items-start justify-between gap-4 py-3">
                            <span class="font-semibold">${escapeHtml(item.label)}</span>
                            <span class="text-right">${escapeHtml(item.displayValue)}</span>
                        </li>
                    `).join('')
                    : '<li class="py-3 text-sm text-[#9a3412]/80">Điền các thông số đầu vào để phiếu dự toán tự động cập nhật.</li>';
            }

            if (previewBullets) {
                previewBullets.innerHTML = bullets.length > 0
                    ? bullets.map((bullet) => `
                        <li class="flex items-start gap-3">
                            <span class="mt-2 size-2 rounded-full bg-[#1d4ed8]"></span>
                            <span>${escapeHtml(bullet)}</span>
                        </li>
                    `).join('')
                    : '<li class="text-sm text-[#1e40af]/80">Không có mô tả chi tiết.</li>';
            }

            if (previewTotalArea) {
                previewTotalArea.textContent = formatArea(result?.converted_area_breakdown?.total_converted_area || result?.totals?.converted_area || 0);
            }

            if (previewBaseArea) {
                previewBaseArea.textContent = formatArea(result?.converted_area_breakdown?.total_base_area || result?.derived?.base_area || 0);
            }

            if (previewFormulaVersion) {
                previewFormulaVersion.textContent = result?.formula_version || 'v1';
            }

            renderPreviewPricing(previewPricing, result);
            renderPreviewComponentBreakdown(previewComponentBreakdown, result);
            renderPreviewRoomBreakdown(previewRoomBreakdown, result);
        });
    };

    const renderGuideModal = (fieldKey = activeGuideFieldKey) => {
        const activeTab = selectedTab();
        const allGuideItems = buildGuideItems(activeLevelCode());
        const focusedItem = fieldKey
            ? allGuideItems.find((item) => item.key === fieldKey) || null
            : null;

        if (fieldKey && !focusedItem) {
            activeGuideFieldKey = null;
        }

        const guideItems = focusedItem ? [focusedItem] : allGuideItems;
        const modalTitle = focusedItem ? focusedItem.guideTitle : defaultGuideModalTitle;
        const modalDescription = focusedItem
            ? `Giải thích chi tiết để khách hàng hiểu rõ field "${focusedItem.fieldLabel}" trước khi chốt thông số dự toán.`
            : defaultGuideModalDescription;

        if (guideModalTitle) {
            guideModalTitle.textContent = modalTitle;
        }

        if (guideModalDescription) {
            guideModalDescription.textContent = modalDescription;
        }

        if (guideModalLevelLabel) {
            guideModalLevelLabel.textContent = activeTab?.dataset.tierName || activeTab?.dataset.levelName || activeLevelCode();
        }

        if (!guideModalList) {
            return;
        }

        guideModalList.innerHTML = guideItems.length > 0
            ? guideItems.map((item) => `
                <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-[#0d6cb6]">${escapeHtml(item.groupLabel)}</span>
                        <h4 class="font-['Plus_Jakarta_Sans'] text-lg font-black text-slate-950">${escapeHtml(item.guideTitle)}</h4>
                    </div>
                    <div class="theme-copy mt-3 text-sm leading-7 text-slate-600">${formatGuideContent(item.guideContent)}</div>
                    ${item.options.length > 0 ? `
                        <div class="mt-4 flex flex-wrap gap-2">
                            ${item.options.map((option) => `
                                <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">${escapeHtml(option)}</span>
                            `).join('')}
                        </div>
                    ` : ''}
                </article>
            `).join('')
            : `
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm leading-7 text-slate-500">
                    Chưa có nội dung hướng dẫn cho cấp dự toán hiện tại. Bạn có thể bổ sung trong admin tại mục Công thức & quy đổi dự toán.
                </div>
            `;
    };

    const syncBodyModalState = () => {
        const hasOpenModal = [modal, guideModal].some((node) => node?.getAttribute('aria-hidden') === 'false');

        document.body.classList.toggle('service-modal-open', hasOpenModal);
    };

    const setModalState = (target, isOpen) => {
        if (!target) {
            return;
        }

        target.classList.toggle('hidden', !isOpen);
        target.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        syncBodyModalState();
    };

    const closeModal = () => {
        setModalState(modal, false);
    };

    const openModal = () => {
        closeGuideModal();
        setModalState(modal, true);
    };

    const closeGuideModal = () => {
        activeGuideFieldKey = null;
        setModalState(guideModal, false);
    };

    const openGuideModal = (fieldKey = null) => {
        activeGuideFieldKey = fieldKey;
        closeModal();
        renderGuideModal(fieldKey);
        setModalState(guideModal, true);
    };

    const syncModal = (displayItems, result) => {
        const activeTab = selectedTab();
        const inputMap = buildInputMap(activeLevelCode());
        const payload = buildPayload(activeLevelCode(), result?.input ? { ...inputMap, ...result.input } : inputMap);

        if (modalTierCode) {
            modalTierCode.value = activeTab?.dataset.tierCode || '';
        }

        if (modalTierName) {
            modalTierName.value = activeTab?.dataset.tierName || '';
        }

        if (modalLevel) {
            modalLevel.value = activeLevelCode();
        }

        if (modalInputPayload) {
            modalInputPayload.value = JSON.stringify(payload);
        }

        if (modalResultPayload) {
            modalResultPayload.value = JSON.stringify(result || {});
        }

        if (modalPageUrl) {
            modalPageUrl.value = window.location.href;
        }

        if (modalTierLabel) {
            modalTierLabel.textContent = activeTab?.dataset.tierName || 'Cấp dự toán';
        }

        if (modalDeliveryText) {
            modalDeliveryText.textContent = activeTab?.dataset.deliveryText || '';
        }

        if (modalTitle) {
            modalTitle.textContent = activeTab?.dataset.tierName
                ? `${defaultModalTitle} - ${activeTab.dataset.tierName}`
                : defaultModalTitle;
        }

        if (modalDescription) {
            modalDescription.textContent = defaultModalDescription;
        }

        if (modalKeyWrap) {
            modalKeyWrap.classList.toggle('hidden', activeTab?.dataset.requiresKey !== 'true');
        }

        if (modalKeyLabel) {
            modalKeyLabel.textContent = activeTab?.dataset.keyLabel || 'Mã key';
        }

        if (modalKeyHelp) {
            modalKeyHelp.textContent = activeTab?.dataset.keyHelp || '';
        }

        renderPreviewSheets(displayItems, result);
    };

    const setTabState = () => {
        const activeTab = selectedTab();
        const levelCode = activeLevelCode();
        const levelInputMap = buildLevelInputMap(levelCode);

        tabs.forEach((tab) => {
            const isActive = tab === activeTab;
            const tabLevelLabel = tab.querySelector('[data-estimate-tab-level-label]');
            const tabBadge = tab.querySelector('[data-estimate-tab-badge]');
            const tabDescription = tab.querySelector('[data-estimate-tab-description]');

            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.classList.toggle('border-[#0d6cb6]', isActive);
            tab.classList.toggle('bg-[#0d6cb6]', isActive);
            tab.classList.toggle('text-white', isActive);
            tab.classList.toggle('shadow-lg', isActive);
            tab.classList.toggle('shadow-[#0d6cb6]/20', isActive);
            tab.classList.toggle('border-[#d7e3f4]', !isActive);
            tab.classList.toggle('bg-[#f8fbff]', !isActive);
            tab.classList.toggle('text-[#0d2a4d]', !isActive);

            tabLevelLabel?.classList.toggle('text-white/70', isActive);
            tabLevelLabel?.classList.toggle('text-[#0d6cb6]', !isActive);
            tabBadge?.classList.toggle('bg-white/15', isActive);
            tabBadge?.classList.toggle('text-white', isActive);
            tabBadge?.classList.toggle('bg-[#dff1ff]', !isActive);
            tabBadge?.classList.toggle('text-[#0d6cb6]', !isActive);
            tabDescription?.classList.toggle('text-white/80', isActive);
            tabDescription?.classList.toggle('text-[#4d6278]', !isActive);
        });

        fieldRows.forEach((row) => {
            const visible = parseLevelList(row.dataset.visibleLevels).includes(levelCode)
                && dependencySatisfied(row, levelInputMap);

            row.classList.toggle('hidden', !visible);
            row.setAttribute('aria-hidden', visible ? 'false' : 'true');
            row.querySelectorAll('[data-estimate-input]').forEach((control) => {
                control.disabled = !visible;
            });
        });

        if (activeBadge) {
            activeBadge.textContent = activeTab?.dataset.tierBadge || (activeTab?.dataset.requiresKey === 'true' ? 'Cần key' : 'Mở');
        }

        if (activeTierName) {
            activeTierName.textContent = activeTab?.dataset.tierName || 'Dự toán cơ bản';
        }

        if (activeLevelLabel) {
            activeLevelLabel.textContent = activeTab?.dataset.levelName || activeLevelCode();
        }

        if (keyStatus) {
            keyStatus.textContent = activeTab?.dataset.requiresKey === 'true'
                ? (activeTab?.dataset.keyHelp || 'Cấp này yêu cầu mã key trước khi gửi.')
                : 'Có thể gửi yêu cầu ngay sau khi kiểm tra thông số.';
        }

        requestButtons.forEach((button) => {
            button.textContent = activeTab?.dataset.buttonLabel || 'Yêu cầu dự toán';
        });
    };

    const renderAll = () => {
        renderCustomSections(activeLevelCode(), buildInputMap(activeLevelCode()));
        const result = calculateEstimate(activeLevelCode());
        const displayItems = buildDisplayItems(activeLevelCode(), result.input);

        showAlert([]);
        renderSummary(displayItems);
        renderBreakdown(result);
        renderPricingCards(result);
        renderComponentTreeBreakdown(result);
        renderRoomBreakdown(result);
        renderIllustration(result);
        renderPreviewSheets(displayItems, result);
        syncModal(displayItems, result);

        if (guideModal?.getAttribute('aria-hidden') === 'false') {
            renderGuideModal();
        }

        if (totalConvertedArea) {
            totalConvertedArea.textContent = formatArea(result?.converted_area_breakdown?.total_converted_area || result?.totals?.converted_area || 0);
        }

        if (totalBaseArea) {
            totalBaseArea.textContent = formatArea(result?.converted_area_breakdown?.total_base_area || result?.derived?.base_area || 0);
        }

        if (formulaVersion) {
            const catalogVersion = `${result?.catalog_version || ''}`.trim();

            formulaVersion.textContent = `Formula ${result?.formula_version || 'v1'} · Price ${result?.price_version || 'v1'}${catalogVersion ? ` · Catalog ${catalogVersion}` : ''}`;
        }

        return { displayItems, result };
    };

        tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => {
                item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
            });

            syncTerraceInputs();
            syncDynamicDefaults();
            setTabState();
            renderAll();
        });
    });

    fieldRows.forEach((row) => {
        const guideButton = row.querySelector('[data-estimate-field-guide-open]');

        if (guideButton) {
            guideButton.addEventListener('click', (event) => {
                event.preventDefault();
                openGuideModal(row.dataset.fieldKey || null);
            });
        }

        row.querySelectorAll('[data-estimate-input]').forEach((control) => {
            const eventName = control.tagName === 'SELECT' || control.type === 'checkbox' ? 'change' : 'input';

            control.addEventListener(eventName, () => {
                syncTerraceInputs(row.dataset.fieldKey || null);
                syncDynamicDefaults(row.dataset.fieldKey || null);
                setTabState();
                renderAll();
            });
        });
    });
    const handleCustomInput = (control) => {
        const groupKey = `${control?.dataset.inputGroup || ''}`.trim();

        if (groupKey === '') {
            return;
        }

        if (groupKey === 'design_options') {
            designOptionState = uniqueCodes(control.checked
                ? [...designOptionState, control.value]
                : designOptionState.filter((code) => code !== control.value));
            renderAll();
            return;
        }

        if (groupKey === 'special_addons') {
            specialAddonState = uniqueCodes(control.checked
                ? [...specialAddonState, control.value]
                : specialAddonState.filter((code) => code !== control.value));
            renderAll();
            return;
        }

        if (groupKey !== 'room_program') {
            return;
        }

        const rowIndex = Number(control.dataset.rowIndex);
        const field = `${control.dataset.field || ''}`.trim();
        const row = roomProgramState[rowIndex];

        if (!row) {
            return;
        }

        if (field === 'room_type') {
            row.room_type = normalizeCodeValue(control.value);
            row.template = compatibleTemplates(row.room_type, activeLevelCode(), currentBuildingType(activeLevelCode()))[0]?.code || '';
            row.extra_items = [];
        } else if (field === 'count') {
            row.count = Math.max(1, Math.trunc(Number(control.value || 1)));
        } else if (field === 'template') {
            row.template = normalizeCodeValue(control.value);
        } else if (field === 'extra_item') {
            row.extra_items = uniqueCodes(control.checked
                ? [...row.extra_items, control.value]
                : row.extra_items.filter((code) => code !== control.value));
        }

        renderAll();
    };

    roomProgramAddButton?.addEventListener('click', () => {
        if (compatibleRoomTypes(activeLevelCode(), currentBuildingType(activeLevelCode())).length === 0) {
            return;
        }

        roomProgramState = [...roomProgramState, defaultRoomProgramRow(activeLevelCode(), currentBuildingType(activeLevelCode()))];
        renderAll();
    });

    landing.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-room-program-action]');

        if (!actionButton) {
            return;
        }

        const action = `${actionButton.dataset.roomProgramAction || ''}`.trim();
        const rowIndex = Number(actionButton.dataset.rowIndex);

        if (action === 'remove' && Number.isInteger(rowIndex)) {
            roomProgramState = roomProgramState.filter((_, index) => index !== rowIndex);
            renderAll();
        }
    });

    landing.addEventListener('change', (event) => {
        const customInput = event.target.closest('[data-estimate-custom-input]');

        if (customInput) {
            handleCustomInput(customInput);
        }
    });

    landing.addEventListener('input', (event) => {
        const customInput = event.target.closest('[data-estimate-custom-input][data-field="count"]');

        if (customInput) {
            handleCustomInput(customInput);
        }
    });

    guideButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openGuideModal();
        });
    });

    requestButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const currentInputMap = buildInputMap(activeLevelCode());
            const errors = validateInputs(activeLevelCode(), currentInputMap);

            if (errors.length > 0) {
                showAlert(errors.slice(0, 3));
                formAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const state = renderAll();

            syncModal(state.displayItems, state.result);
            openModal();
        });
    });

    modal?.querySelectorAll('[data-estimate-close], [data-estimate-backdrop]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    guideModal?.querySelectorAll('[data-estimate-guide-close], [data-estimate-guide-backdrop]').forEach((button) => {
        button.addEventListener('click', closeGuideModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (modal?.getAttribute('aria-hidden') === 'false') {
            closeModal();
        }

        if (guideModal?.getAttribute('aria-hidden') === 'false') {
            closeGuideModal();
        }
    });

    syncTerraceInputs();
    syncDynamicDefaults();
    setTabState();
    renderAll();

    if (modal?.dataset.openOnLoad === 'true') {
        openModal();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEstimateLanding, { once: true });
} else {
    initEstimateLanding();
}

document.addEventListener('livewire:navigated', initEstimateLanding);
