<?php

namespace Ac\Pm\SpreadSheet;

final class Html
{
    private static $assetsShown = false;
    
    private const ASSETS_PATH = "/local/modules/ac.pm/lib/SpreadSheet/Assets/";
    
    public static function includeAssets(): void
    {
        if (self::$assetsShown) {
            return;
        }
        global $APPLICATION;
        $APPLICATION->AddHeadScript(self::ASSETS_PATH . "spreadsheet_script.js", true);
        $APPLICATION->AddHeadScript(self::ASSETS_PATH . "jsuites/jsuites.js", true);
        $APPLICATION->AddHeadScript(self::ASSETS_PATH . 'jspreadsheet/index.js', true);
        $APPLICATION->SetAdditionalCSS("/local/css/bootstrap.min.css", true);
        $APPLICATION->SetAdditionalCSS("/local/css/font-awesome/css/font-awesome.min.css", true);
        $APPLICATION->SetAdditionalCSS(self::ASSETS_PATH . "jsuites/jsuites.css", true);
        $APPLICATION->SetAdditionalCSS(self::ASSETS_PATH . "jspreadsheet/jspreadsheet.css", true);
        $APPLICATION->SetAdditionalCSS(self::ASSETS_PATH . "spreadsheet_styles.css", true);
        
        //TODO: Вынести для разных классов ещё дополнения CSS и JS раздельно
        
        self::$assetsShown = true;
    }
    
    public static function showAccessDenied(): void
    {
        throw new \RuntimeException("У вас не хватает прав на просмотр данного раздела");
    }
    
    public static function showPageHeader(): void
    {?>
        <div>
            <a href="index.php"><< Вернуться на основную страницу</a>
        </div>
    <?php
    }
    
    public static function showTableHtml(Main $table): void
    {
        global $APPLICATION;
        $ajaxRouter = $table::AJAX_ROUTER;
        $tableClass = addslashes(trim($table->getTableClass(), '\\'));
        $checkString = Security::getCheckString($table->getTableClass());
        $APPLICATION->setTitle($table->getTitle());
        
        if ($table->showFilter) {
            $table->showFilterBlock();
        }
    ?>
    <div class="p-xl-4 p-md-3 p-sm-2 p-1 jstable-main" style="min-height: calc(100vh - 210px);">
        <div class="row">
            <div class="col">
                <?// Постраничная навигация?>
                <?if ($table->limit < $table->records_count) : ?>
                    <?php
                        $recordsLimit = $table->offset + $table->limit;
                        $recordsLimit = $recordsLimit > $table->records_count ? $table->records_count : $recordsLimit;

                        $urlParams = '';
                        if (!empty($table->filter)) {
                            $urlParams = '&' . http_build_query($table->filter);
                        }
                    ?>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-secondary <?= $table->page == 1 ? 'disabled' : ''?>" href="<?= $APPLICATION->GetCurPage().'?page='. $table->prev_page . '&limit=' . $table->limit . $urlParams ?>">Назад</a>&nbsp;
                        <a class="btn btn-sm btn-secondary <?= $table->page == $table->pages_count ? 'disabled' : ''?>" href="<?= $APPLICATION->GetCurPage().'?page='. $table->next_page . '&limit=' . $table->limit . $urlParams?>">Вперед</a>
                        &nbsp;Страница №<?= $table->page?> из <?= $table->pages_count?> (записи с <?= $table->offset + 1?> по <?= $recordsLimit ?> из <?= $table->records_count?>)
                    </div>
                <?endif;?>
            </div>
            <?php
                //Сохранение сразу при изменении
                if ($table->getUserTableParams()['saveOnChange'] === true):
            ?>
                <div class="col px-5 pt-3 text-right">
                    <div class="custom-control custom-switch m-2">
                        <input type="checkbox" class="custom-control-input" id="online-edit-switch" checked>
                        <label class="custom-control-label" for="online-edit-switch" title="Данные в таблице будут сохраняться сразу при изменении ячеек, а не только при нажатии кнопки Сохранить">&nbsp;Сохранять онлайн</label>
                    </div>
                </div>
            <?php endif ?>
        </div>
        
        
        <?// Основной блок для вывода таблицы?>
        <div class="py-3 spreadsheet-scroll-x">
            <div id="spreadsheet"></div>
        </div>
        
        <?// Блок кнопок?>
        <?php
            //Сохранение всех данных
            if ($table->getUserTableParams()['canSave'] === true):
        ?>
            <div class="py-3">
                <button class="btn btn-success btn-sm" id="save" title="Сохранить данные таблицы"><i class="fa fa-save"></i>&nbsp;Сохранить данные</button>
                <!-- <button class="btn btn-info btn-sm ml-1 " id="insert-row" title="Добавить строку"><i class="fa fa-plus-square"></i>&nbsp;Добавить строку</button> -->
                <!-- <a href="#" class="btn btn-sm btn-info" id="insert-row"><i class="fa fa-plus-square"></i>&nbsp;Добавить строку</a> -->
                <button onClick="table.table.undo();" class="btn btn-danger btn-sm ml-1" id="undo" title="Отменить последнее действие"><i class="fa fa-undo"></i></button>
                <button onClick="table.table.redo();" class="btn btn-danger btn-sm" id="redo" title="Повторить последнее отмененное действие"><i class="fa fa-repeat"></i></button>
                <!--<button class="btn btn-danger btn-sm " id="delete_last" title="Удалить последнюю строку" onclick="table.table.deleteRow(-1, 1);"><i class="fa fa-trash"></i>&nbsp;Удалить последнюю строку</button>-->
            </div>
        <?php endif; ?>
    </div>
    <script>
        var table = {
            userParams : <?= json_encode($table->getuserTableParams())?>,
            getTransferData : function(actionString = 'saveData') {
                return {
                    sessid: BX.bitrix_sessid(),
                    sheet: '<?= $tableClass ?>',
                    check: '<?= $checkString ?>',
                    action: actionString
                }
            },
            ajaxRouter : '<?= $ajaxRouter?>'
        };

        $("#save").click(function() {
            let save_data = table.table.getJson(false);
            table.updateAjax(save_data);
        });
    </script>
<?php }
    
    public static function showTableStart(Main $table)
    {
        self::showColumnScript($table->getColumns(), $table->nestedHeaders);
        $data = json_encode($table->getData());
        $userTableParams = $table->getUserTableParams();
        $allowInsertRow = json_encode($userTableParams['allowInsertRow']);
        $allowDeleteRow = json_encode($userTableParams['allowDeleteRow']);
        echo <<<SHOWTABLE
<script>
    $(document).ready(function() {
        table.options = {
            tableOverflow: true,
            tableWidth:'100%',
            tableHeight:'70vh',
            data: {$data},
            onchange: table.changed,
            oninsertrow: table.onInsertRow,
            onpaste: table.pasted,
            onload: table.loaded,
            columns: table.columns,
            nestedHeaders: table.nestedColumns,
            allowManualInsertRow: true,
            allowManualInsertColumn: false,
            allowInsertRow: $allowInsertRow,
            allowDeleteRow: $allowDeleteRow,
            columnResize: true,
            rowResize: true,
            contextMenu: table.contextMenu,
            selectionCopy: true,
            loadingSpin: true,
            wordWrap: true,
            minSpareRows: 0
        };
        table.tableStart();
    });
</script>
SHOWTABLE;
    
    }
    
    public static function showColumnScript(array $columns, $nestedColumns = null): void
    {
        echo '<script> table.columns = ' . json_encode($columns, 1) . ';' .
            ($nestedColumns ? ('table.nestedColumns = ' . json_encode($nestedColumns, 1) . ';' ) : '') .
            '</script>';
    }
}