<?php

namespace Ac\Pm\SpreadSheet;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\Base;
use OutOfBoundsException;
use RuntimeException;

class Main
{
    protected const DEFAULT_PAGE_LIMIT = 100;
    
    protected string $tableClass;
    protected array $tableColumns = [];
    public array $columnsType = [];
    public array $columnsParams = [];
    public ?array $nestedHeaders = null;
    
    protected array $defaultUserTableParams = [
        'canSave' => true,
        'saveOnChange' => true,
        'checkboxSendField' => null,
        'prefilledFields' => [],
        'allowInsertRow' => true,
        'allowDeleteRow' => true,
        'canPaste' => true,
    ];
    
    protected array $dropdowns = [];
    
    public int $prev_page, $page, $next_page, $pages_count, $records_count;
    
    public ?int $limit = null;
    public ?int $offset = null;
    protected array $select;
    
    public bool $showHeader = true;
    public bool $useFilter = true;
    public bool $fillEmptyData = true;
    
    
    public array $filter = [];
    public array $exportFilter = [];
    public array $data = [];
    private array $columns = [];
    private array $userTableParams = [];
    private array $request = [];
    
    //Start params
    public bool $checkInsertRow = true;
    public bool $columnResize = true;
    public bool $rowResize = true;
    public string $defaultColumnWidth = '100';
    public string $dateFormatDefault = 'dd.mm.YYYY';
    
    public RightsModel $rightsModel;
    
    public const AJAX_ROUTER = '/local/modules/ac.pm/lib/SpreadSheet/ajaxRouter.php';
    
    /**
     * Пример использования в \Ac\Pm\Spg\Starters\MainStarter
     * Main constructor.
     * @param string $tableClass
     */
    
    public function __construct(string $tableClass)
    {
        if (!is_a($tableClass, '\Ac\Pm\SpreadSheet\MainTable', true)) {
            throw new RuntimeException('Передан не верный класс для создания таблицы ' . $tableClass);
        }
        $this->tableClass = $tableClass;
        $map = ($this->tableClass)::getMap();
        foreach ($map as $field) {
            $this->tableColumns[$field->getName()] = $field->getTitle();
        }
        $this->request = Application::getInstance()->getContext()->getRequest()->toArray();
    }
    
    public function getTitle(): string
    {
        if (!empty($this->tableClass)) {
            return ($this->tableClass)::TITLE ?? '';
        }
        return '';
    }
    
    public function getTableClass(): string
    {
        return $this->tableClass;
    }
    
    public function setRightsModel(RightsModel $rightsModel): void
    {
        $this->rightsModel = $rightsModel;
        $readonlyColumns = $this->rightsModel->getReadonlyColumns();
        $this->setReadonly($readonlyColumns);
    }
    
    public function setFilterFromRightsModel(): void
    {
        if (!isset($this->rightsModel)) {
            throw new RuntimeException('Не установлена модель прав для поллучения фильтра отображаемых данных');
        }
        $this->filter = $this->rightsModel->getMainFilter();
    }
    
    public function getData(): array
    {
        return $this->data;
    }
    
    public function getColumns(): array
    {
        return $this->columns;
    }
    
    public function getUserTableParams(): array
    {
        return $this->userTableParams;
    }
    
    public function mergeUserTableParams(array $otherParams): void
    {
        $this->userTableParams = array_merge($this->userTableParams, $otherParams);
    }
    
    public function show(): void
    {
        $this->showPageStart();
        $this->setData();
        $this->showPageEnd();
    }
    
    public function checkExport(): void
    {
        if ($this->request['export'] && (new $this->tableClass instanceof ExportableInterface)) {
            $this->setExportFilter();
            $this->setExportSelect();
            $this->prepareData(false);
            $this->tableClass::export($this->data ?: []);
            die;
        }
    }
    
    public function showPageStart(): void
    {
        if (!empty($this->rightsModel) && !$this->rightsModel->canVisit()) {
            Html::showAccessDenied();
            return;
        }
        $this->checkExport();
    
        Html::includeAssets();

        //Подключение дополнительных скриптов для формы
        if (method_exists($this->tableClass, 'setCustomJs')) {
            $this->tableClass::setCustomJs();
        }

        if ($this->showHeader) {
            Html::showPageHeader();
        }
        $this->setUserTableParams();
        $this->setFilter();
        $this->setData();
    }
    
    public function setData(): void
    {
        $this->setTableNavigationParams();
        $this->prepareData();
    }
    
    public function showPageEnd(): void
    {
        Html::showTableHtml($this);
        $this->setColumns();
        Html::showTableStart($this);    }
    
    protected function setFilter(): void
    {
        if ($this->useFilter) {
            $tableMap = $this->tableClass::getMap();
            foreach ($tableMap as $field) {
                if ((($fieldValue = $this->request[$fieldName = $field->getName()]) !== null) && ($fieldValue !== '')) {
                    if (!in_array($fieldName, $this->userTableParams['prefilledFields'])) {
                        $this->filter[$fieldName] = $fieldValue;
                    }
                }
            }
        }
    }
    
    protected function setExportFilter(): void
    {
        $this->setFilter();
        if ($this->exportFilter = $this->request['export_filter'] ?: []) {
            $this->filter = array_merge($this->filter, $this->exportFilter);
        }
    }
    
    protected function setExportSelect(): void
    {
        if (isset($this->rightsModel)) {
            $exportHiddenFields = defined($this->tableClass . '::EXPORT_HIDDEN_FIELDS') ? ($this->tableClass)::EXPORT_HIDDEN_FIELDS : [];
            $this->select = array_diff(array_keys($this->tableColumns), $this->rightsModel->getHiddenColumns(), $exportHiddenFields);
        }
    }
    
    protected function setUserTableParams(): void
    {
        $this->userTableParams = $this->defaultUserTableParams;
        
        if (!empty($this->rightsModel)) {
            
            $this->rightsModel->setUserTableParams($this->userTableParams);
            
            if ($userCheckboxSendField = $this->rightsModel->getCheckboxSendField()) {
                $this->userTableParams['checkboxSendField'] = $userCheckboxSendField;
            }
        }
        $this->userTableParams['prefilledFields'] = $this->tableClass::getPrefilledFields() ?: null;
    }
    
    public function setDropdown(string $code, array $dropdownValues, bool $skipError = false): void
    {
        if (!array_key_exists($code, $this->tableColumns)) {
            if ($skipError) {
                return;
            }
            throw new OutOfBoundsException("Для установления значения выпадающего списка передан не существующий код столбца $code");
        }
        $this->columnsType[$code] = 'dropdown';
        $this->dropdowns[$code] = $dropdownValues;
    }
    
    public function setColumnParam(string $code, string $param, $value): void
    {
        if (!array_key_exists($code, $this->tableColumns)) {
            throw new OutOfBoundsException("Для установления значения выпадающего списка передан не существующий код столбца $code");
        }
        $this->columnsParams[$code][$param] = $value;
    }
    
    public function setWidth(array $data): void
    {
        foreach ($data as $columnCode => $widthValue) {
            $this->setColumnParam($columnCode, 'width', $widthValue);
        }
    }

    public function setDropdownParams(array $data): void
    {
        foreach ($data as $columnCode => $arColumnProperty) {
            foreach ($arColumnProperty as $key => $property) {
                if ($key === 'source') {
                    $this->setDropdown($columnCode, $property);
                } else {
                    $this->setColumnParam($columnCode, $key, $property);
                }
            }
        }
    }
    
    public function setReadonly(array $data): void
    {
        foreach ($data as $columnCode) {
            $this->setColumnParam($columnCode, 'readonly', true);
        }
    }
    
    /**
     * Для поля типа numeric существуют доп проверки.
     * 1. Всегда проверяется, что даже при копипасте добавляется число (с минусом, точка или запятая, с десятичным знаком или без)
     * 2. Если decimal === null, то также проверяется, что нет никакого десятичного знака (запятой или точки)
     * 3. Если в options добавить isPositive === true - проверяется на положительное число >= 0
     * 4. maxDigit - указывается максимальное количество цифр, тестировалось только на целых), например телефон без прочих знаков или индекс
     * Если число не проходит хоть одну из этиз проверок, при добавлении и вставке в ячейку подставляется пустая строка.
     * Данный раздел необходимо корректировать с учётом дополнительных доработок
     * Также можно добавить свои, эксклюзивные проверки в методах table.changedAdditional и table.pastedAdditional для отдельных форм
     */
    protected function setColumns(): void
    {
        $columns = [];
        foreach ($this->tableColumns as $columnCode => $columnTitle) {
            
            $columnType = 'text';
            if ($this->columnsType[$columnCode]) {
                $columnType = $this->columnsType[$columnCode];

                if (($columnType === 'calendar') && is_null($this->columnsParams[$columnCode]['options'])) {
                    $this->columnsParams[$columnCode]['options'] = [
                        'format' => $this->dateFormatDefault,
                        'months' => ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
                        'weekdays_short' => ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                        'textDone' => 'Подтвердить',
                        'textReset' => 'Сбросить',
                        'textUpdate' => 'Обновить',
                        'type' => 'default'
                    ];
                }
            }
            
            if ($this->isHiddenColumn($columnCode)) {
                $columnType = 'hidden';
            }
            
            $columns[] = [
                'type' => $columnType,
                'title' => $columnTitle,
                'readOnly' => $this->columnsParams[$columnCode]['readonly'] ?? false,
                'width' => $this->columnsParams[$columnCode]['width'] ?? $this->defaultColumnWidth,
                'align' => $this->columnsParams[$columnCode]['align'],
                'mask' => $this->columnsParams[$columnCode]['mask'],
                'decimal' => $this->columnsParams[$columnCode]['decimal'],
                'options' => $this->columnsParams[$columnCode]['options'],
                'source' => $this->dropdowns[$columnCode],
                'autocomplete' => $this->columnsParams[$columnCode]['autocomplete'],
                'url' => $this->columnsParams[$columnCode]['url'],
                'wrap' => true
            ];
        }
        $this->columns = $columns;
    }
    
    protected function isHiddenColumn(string $columnCode): bool
    {
        if (empty($this->rightsModel)) {
            return false;
        }
        return in_array($columnCode, $this->rightsModel->getHiddenColumns());
    }
    
    private function createClassTable(): void
    {
        $tableInstance = Base::getInstance($this->tableClass);
        if (!Application::getConnection()->isTableExists($tableInstance->getDBTableName())) {
            $tableInstance->createDBTable();
        }
    }
    
    private function setTableNavigationParams(): void
    {
        //Взято из исходных скриптов, пока толком не проверялось
        try {
            $this->records_count = $this->tableClass::getCount($this->filter);
        } catch (SqlQueryException $e) {
            $this->createClassTable();
            $this->records_count = 0;
        }
        $this->limit = $this->request['limit'] ?? self::DEFAULT_PAGE_LIMIT;
        $this->page = $this->request['page'] ?? 1;
        $this->pages_count = ceil($this->records_count / $this->limit);
        $this->prev_page = $this->page > 1 ? $this->page - 1 : $this->page;
        $this->next_page = $this->page + 1;
        $this->offset = $this->request['offset'] ?? (($this->page * $this->limit) - $this->limit);
    }
    
    private function prepareData(?bool $forceFillEmptyData = null): void
    {
        //TODO: Добавить обработчики получаемых данных
        if (!isset($this->select)) {
            $this->select = array_keys($this->tableColumns);
        }
        $this->data = $this->loadData();
        if (empty($this->data)
            && ($this->fillEmptyData || (!is_null($forceFillEmptyData) && $forceFillEmptyData))
        ) {
            $this->fillFirstEmptyRow();
        }
    }
    
    private function loadData(): array
    {
        $entity = $this->tableClass::getList([
            'select' => $this->select,
            'filter' => $this->filter,
            'order' => ['ID'],
            'limit' => $this->limit,
            'offset' => $this->offset
        ]);
        return $entity->fetchAll() ?: [];
    }
    
    private function fillFirstEmptyRow(): void
    {
        $values = array_fill_keys(array_keys($this->tableColumns), '');
        if ($this->userTableParams['prefilledFields']) {
            $values = array_merge($values, $this->userTableParams['prefilledFields']);
        }
        $this->data = [$values];
    }
}