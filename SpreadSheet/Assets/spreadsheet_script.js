$(document).ready(function() {

    table.findColumnIndex = function (columnCode) {
        for (var columnIndex in table.options.columns) {
            if (table.options.columns[columnIndex].name === columnCode) {
                return columnIndex;
            }
        }
        return false;
    };

    table.findColumnCode = function (columnIndex) {
        return table.options.columns[columnIndex].name ? table.options.columns[columnIndex].name : '';
    };

    table.setReadonlyRow = function (cellY) {
        let rowData = table.table.getRowData(cellY);
        for (var cellX in rowData) {
            let cell = table.table.getCellFromCoords(cellX, cellY);
            if (typeof cell != 'undefined') {
                cell.classList.add('readonly');
                if ($(cell).find('input')) {
                    $(cell).find('input').prop('disabled', true);
                }
            }
        }
    };

    table.setReadonlyRowsAfterLoad = function () {

        //Блокировка по строкам
        if (typeof table.userParams.readonlyRowIndexes !== 'undefined') {
            for (let rowIndex in table.userParams.readonlyRowIndexes) {
                table.setReadonlyRow(table.userParams.readonlyRowIndexes[rowIndex]);
            }
        }

        //Блокировка по колонкам
        if ((table.userParams.checkboxSendField === null)) {
            return;
        }
        let columnIndex = table.findColumnIndex(table.userParams.checkboxSendField);
        if (columnIndex === false) {
            return;
        }

        if (typeof table.table !== 'undefined') {

            let columnData = table.table.getColumnData(columnIndex);
            for (let rowIndex in columnData) {
                if (columnData[rowIndex]) {
                    table.setReadonlyRow(rowIndex);
                }
            }
        }
    };

    table.onInsertRow = function (el, rowNumber, numOfRows, rowRecords, insertBefore) {
        if (table.userParams.prefilledFields) {
            table.options.changedOff = true;
            let realY = insertBefore ? rowNumber : rowNumber + 1;
            for (var fieldCode in table.userParams.prefilledFields) {
                let columnIndex = table.findColumnIndex(fieldCode);
                if (columnIndex !== false) {
                    let readonlyColumn = table.options.columns[columnIndex].readOnly;
                    if (readonlyColumn) {
                        let cell = table.table.getCellFromCoords(columnIndex, realY);
                        if (cell) cell.classList.remove('readonly');
                    }
                    table.table.setValueFromCoords(columnIndex, realY, table.userParams.prefilledFields[fieldCode]);
                    if (readonlyColumn) {
                        let cell = table.table.getCellFromCoords(columnIndex, realY);
                        if (cell) cell.classList.add('readonly');
                    }
                }
            }
            table.options.changedOff = false;
        }
    };

    table.contextMenu = function (obj, x, y, e) {
        let items = [];

        if (y != null) {
            // Insert new row
            if (obj.options.allowInsertRow === true) {
                items.push({
                    title: obj.options.text.insertANewRowBefore,
                    onclick: function () {
                        obj.insertRow(1, parseInt(y), 1);
                    }
                });

                items.push({
                    title: obj.options.text.insertANewRowAfter,
                    onclick: function () {
                        obj.insertRow(1, parseInt(y));
                    }
                });
            }

            if (obj.options.allowDeleteRow === true) {
                items.push({
                    title: obj.options.text.deleteSelectedRows,
                    onclick: function () {
                        let confirm = window.confirm("Выбранные строки (КРОМЕ ОТПРАВЛЕННЫХ) удалятся из базы данных, вы уверены что хотите удалить?");

                        if (confirm) {

                            var transferData = table.getTransferData('deleteRow');
                            obj.getSelectedRows().forEach(function (elem, index, arr) {
                                let y = $(elem).data('y');
                                let dataRow = obj.getJsonRow(y);
                                console.log(y, dataRow);

                                let id = parseInt(dataRow['ID']);
                                console.log(id);
                                if (!id) {
                                    table.deleteRow(parseInt(y));
                                } else {
                                    let isSendCheckboxField = (table.userParams.checkboxSendField !== null) && dataRow[table.userParams.checkboxSendField];
                                    if (!isSendCheckboxField) {
                                        transferData.data = id;
                                        $.ajax({
                                            method: "POST",
                                            url: table.ajaxRouter,
                                            data: transferData
                                        }).done(function (result) {
                                            result = JSON.parse(result);
                                            if (!result.error) {
                                                console.log(parseInt(y));
                                                table.deleteRow(parseInt(y));
                                                //options.delEl.push(id);
                                            } else {
                                                alert(result.error);
                                            }
                                        });
                                    }
                                }
                            });
                        }

                    }
                });
            }

            // Line
            items.push({type: 'line'});
        }

        // Copy
        items.push({
            title: obj.options.text.copy,
            shortcut: 'Ctrl + C',
            onclick: function () {
                obj.copy(true);
            }
        });

        // Paste
        if (table.userParams.canPaste === true) {
            items.push({
                title: obj.options.text.paste,
                shortcut: 'Ctrl + V',
                onclick: function () {
                    if (obj.selectedCell) {
                        navigator.clipboard.readText().then(function (text) {
                            if (text) {
                                jexcel.current.paste(obj.selectedCell[0], obj.selectedCell[1], text);
                            }
                        });
                    }
                }
            });
        }

        return items;
    };

    table.saveNewData = function (newData) {
        table.options.changedOff = true;
        for (var rowIndex in newData) {
            for (var columnCode in newData[rowIndex]) {
                let columnIndex = table.findColumnIndex(columnCode);
                if (columnIndex !== false) {
                    table.table.setValueFromCoords(columnIndex, rowIndex, newData[rowIndex][columnCode]);
                }
            }
        }
        table.options.changedOff = false;
    };

    table.updateAjax = function (save_data) {
        let transferData = table.getTransferData();
        transferData.data = save_data;
        $.post(table.ajaxRouter, transferData, function (result) {
            result = JSON.parse(result);
            if (result.newData) {
                table.saveNewData(result.newData);
            }
            if (result.message) {
                alert(result.message);
            }
            if (result.error) {
                let errorText = '';
                if (result.errorData && result.errorData.errorRow) {
                    var errorRow = parseInt(result.errorData.errorRow) + 1;
                    errorText = "Ошибка в строке " + errorRow + ' : ';
                }
                errorText = errorText + result.error;
                alert(errorText);
            }
            let isSendCheckboxField = (table.userParams.checkboxSendField !== null);
            if (isSendCheckboxField) {
                for (var rowIndex in save_data) {
                    if ((typeof errorRow === 'undefined') || rowIndex < errorRow) {
                        if (save_data[rowIndex][table.userParams.checkboxSendField]) {
                            table.setReadonlyRow(rowIndex);
                        }
                    } else {
                        let checkboxColumnIndex = table.findColumnIndex(table.userParams.checkboxSendField);
                        if (checkboxColumnIndex !== false) {
                            table.options.changedOff = true;
                            table.table.setValueFromCoords(checkboxColumnIndex, rowIndex, false, true);
                            table.options.changedOff = false;
                        }
                    }
                }
            }
        });
    };

    table.changed = function (obj, cell, x, y, val) {

        if (table.options.changedOff) {
            return;
        }

        //TODO: Нужны разные тексты + модалка
        let isSendCheckboxField = (table.userParams.checkboxSendField !== null) && (table.findColumnCode(x) === table.userParams.checkboxSendField);
        if (isSendCheckboxField && val) {
            if (!confirm('После оправки данные в строке нельзя будет изменить. Вы уверены, что хотите отправить?')) {
                table.options.changedOff = true;
                table.table.setValueFromCoords(x, y, false, true);
                table.options.changedOff = false;
                return;
            }
        }

        if ((table.userParams.saveOnChange && $('#online-edit-switch').prop('checked')) || (isSendCheckboxField && val)) {
            table.options.changedOff = true;

            var transferData = table.getTransferData();
            transferData.data = {[y]: table.table.getJsonRow(y)};

            $.ajax({
                method: "POST",
                url: table.ajaxRouter,
                data: transferData
            })
            .done(function (result) {
                result = JSON.parse(result);
                if (isSendCheckboxField) {
                    if (result.error) {
                        table.table.setValueFromCoords(x, y, false, true);
                    } else {
                        table.setReadonlyRow(y);
                    }
                }

                //TODO: Вывести на попап!
                if (result.error) {
                    alert(result.error);
                }

                if (result.newData) {
                    table.saveNewData(result.newData);
                }
                
                table.options.changedOff = false;
            });
        }

        let columnType = table.table.getColumnOptions(x).type;
        if (columnType === 'numeric') {
            table.changedNumeric(obj, cell, x, y, val);
        }

        if (typeof table.changedAdditional === 'function') {
            table.changedAdditional(obj, cell, x, y, val);
        }
    };

    table.pasted = function(obj, data, x, y) {

        data.forEach(function indexRow(currentRow, iY, arrayY) {
            currentRow.forEach(function indexColumn(val, iX, arrayX) {
                let currentY = parseInt(y) + parseInt(iY);
                let currentX =  parseInt(x) + parseInt(iX);
                let columnOptions = table.table.getColumnOptions(currentX);
                if (columnOptions.type === 'numeric') {
                    data[iY, iX] = table.changedNumeric(obj, null, currentX, currentY, val);
                }
            });

        });

        if (typeof table.pastedAdditional === 'function') {
            table.pastedAdditional(obj, data, x, y);
        }
    }

    table.changedNumeric = function (obj, cell, x, y, val) {
        let columnOptions = table.table.getColumnOptions(x);
        let columnMoreOptions = columnOptions.options;
        let strval = val.toString();

        //Проверка на число при вставке
        let reg1 = new RegExp(`^[+-]?[0-9]+\\.?[0-9]*$`);
        let reg2 = new RegExp(`^[+-]?[0-9]+\\,?[0-9]*$`);
        if (!(reg1.test(strval) || reg2.test(strval))) {
            console.log('numeric test break1', strval);
            table.table.setValueFromCoords(x, y, '');
            return '';
        }

        //Проверка на отсутствие десятичного знака при вставке
        if (columnOptions.decimal === null) {
            let reg3 = new RegExp(/\.|,/gi);
            if (reg3.test(strval)) {
                console.log('numeric test break2', strval);
                table.table.setValueFromCoords(x, y, '');
                return '';
            }
        }

        //isPositive
        if (columnMoreOptions.isPositive && (val < 0)) {
            console.log('numeric test break3', strval);
            table.table.setValueFromCoords(x, y, '');
            return '';
        }

        //maxDigits
        if (columnMoreOptions.maxDigits && val) {
            let strval = val.toString();
            let reg4 = new RegExp(`^\\d{${columnMoreOptions.maxDigits}}$`);
            if (!reg4.test(strval)) {
                console.log('numeric test break4', strval);
                table.table.setValueFromCoords(x, y, '');
                return '';
            }
        }
        return val;
    };

    table.loaded = function(instance) {
        var tableReadonlyInterval = setInterval(function(){
            if (typeof table.table !== 'undefined') {
                clearInterval(tableReadonlyInterval);
                table.setReadonlyRowsAfterLoad();
            }
        }, 10);
    };

    table.deleteRow = function(y) {
        if (table.table.getData().length > 1) {
            table.table.deleteRow(y);
        } else {
            table.table.insertRow();
            table.table.deleteRow(y);
        }
    }

    table.tableStart = function() {
        setTimeout(function(){
            table.table = jspreadsheet(document.getElementById('spreadsheet'), table.options);
        }, 200);
    };
});