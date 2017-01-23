$.fn.ajaxgrid = function(options) {
    checkOptions(options);

    var ajaxProductLink = options.dataUrl;
    var rootElement = this[0];
    var table;
    var tableBody =document.createElement('tbody');
    var paginationWrapper = document.getElementById('pagination-wrapper');
    var paginationUlCSS = 'btn-group';
    var paginationLiCSS = 'btn-group__item'
    var activePaginator;
    var activePaginatorNumber = 1;
    var elementsInDatabase;
    var paginatorElementsOnPage = options.rowsPerPage;
    var editUrl = options.editUrl;
    var key = options.key;
    var paginationButtonsCount;
    var activeRow;
    var activeRowField;
    var sortOrder;
    var filterbyfield;
    var pattern;
    rootElement.appendChild(createGridTable());
    declareInputKeyDown();
    getProductsAjax();
    paginationWrapper.onclick = function (event) {
        delegatePaginationClick(event);
    }
    function createGridTable() {
        table = document.createElement('table');
        table.classList.add('table-striped');
        table.classList.add('table');
        var tableHeader = createTableHeader();
        tableHeader.onclick = function (event) {
            delegateTableHeaderClick(event);
        }
        table.appendChild(tableHeader);
        table.appendChild(tableBody);
        return table;
    }
    function createTableHeader() {
        var tableHead = document.createElement('thead');
        var tableRow = document.createElement('tr');
        for (var i = 0; i < options.sortableColumns.length; i++) {
            var tableHeader = document.createElement('th');
            tableHeader.classList.add('table-header');
            for (var j = 0; j < options.filterableColumns.length; j++) {
                if (options.sortableColumns[i] == options.filterableColumns[j]) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'text');
                    input.setAttribute('class', 'form-control filter-input');
                    input.setAttribute('field', options.sortableColumns[i]);
                    tableHeader.appendChild(input);
                }
            }

            tableHeader.setAttribute('field', options.sortableColumns[i]);
            tableHeader.innerHTML += options.sortableColumns[i];
            tableRow.appendChild(tableHeader);


        }
        var tableHeader = document.createElement('th');
        tableHeader.classList.add('table-header');
        tableHeader.setAttribute('inactive', 'false');
        tableHeader.innerHTML = 'Edit';
        tableRow.appendChild(tableHeader);
        tableHead.appendChild(tableRow);
        return tableHead;
    }
    function createTableRow(element) {
        var tableRow = document.createElement('tr');
        for (var i = 0; i < options.sortableColumns.length; i++) {
            var tableItem = document.createElement('td');
            var caption = (options.sortableColumns[i]).toString();
            tableItem.innerHTML = element[caption];
            tableItem.setAttribute('caption', caption);
            tableRow.appendChild(tableItem);
        }
        var tableItem = document.createElement('td');
        var editLink = document.createElement('a');
        editLink.setAttribute('href', editUrl+element.id+"/edit");
        editLink.innerHTML = 'Edit';
        tableItem.appendChild(editLink);
        tableRow.appendChild(tableItem);
        return tableRow;
    }
    function createPaginationLi(number, active) {
        var li = document.createElement('li');
        li.setAttribute('class', paginationLiCSS);
        var button = document.createElement('button');
        button.setAttribute('class', 'btn btn--basic');
        button.innerHTML = number;
        if (active === 'true') {
            button.setAttribute('class', 'btn btn--basic current');
        }
        li.appendChild(button);

        return li;
    }
    function createPaginationControl(type) {
        var li = document.createElement('li');
        li.setAttribute('class', paginationLiCSS);
        var i = document.createElement('i');
        i.setAttribute('class', 'i-chevron-'+type);
        i.setAttribute('control', type);
        li.appendChild(i);

        return li;
    }
    function highlightPaginator(node) {
        if (activePaginator) {
            activePaginator.classList.remove('current');
        }
        activePaginator = node;
        activePaginator.classList.add('class', 'current');
        activePaginatorNumber = parseInt(activePaginator.innerHTML);
    }
    function delegatePaginationClick(event) {
        var target = event.target;
        if (target.tagName === 'LI' || target.tagName === "DIV") {
            return;
        } else if (target.tagName === 'I') {

            if (target.getAttribute('control') === 'left') {
                if (activePaginatorNumber - 1 == 0) {
                    return;
                }
                activePaginatorNumber -= 1;
                getProductsAjax();
            } else if (target.getAttribute('control') === 'right') {
                if (activePaginatorNumber + 1 > paginationButtonsCount) {
                    return false;
                }
                activePaginatorNumber += 1;
                getProductsAjax();
            }

        } else if (target.tagName === 'BUTTON') {
            highlightPaginator(target);
            getProductsAjax();
        }
    }
    function deleteChilds(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
        return element;
    }
    function createPaginationView(elementsOnPage, allElements, activeElement) {
        deleteChilds(paginationWrapper);
        var paginationList = document.createElement('ol');
        paginationList.setAttribute('class', paginationUlCSS);
        if (allElements <= elementsOnPage) {
            return;
        }

        paginationButtonsCount = Math.ceil(allElements / elementsOnPage);
        var leftControl = createPaginationControl('left');
        var rightControl = createPaginationControl('right');
        paginationList.appendChild(leftControl);

        for (var i = 1; i <= paginationButtonsCount; i++) {

            var li;
            if (i === activeElement) {
                li = createPaginationLi(i, 'true');
            } else {
                li = createPaginationLi(i);
            }
            paginationList.appendChild(li);

        }
        paginationList.appendChild(rightControl);
        paginationWrapper.appendChild(paginationList);

    }
    function getProductsAjax() {
        deleteChilds(tableBody);
        $.getJSON(ajaxProductLink, {
            page: activePaginatorNumber,
            perpage: paginatorElementsOnPage,
            sortbyfield: activeRowField,
            order: sortOrder,
            filterbyfield: filterbyfield,
            pattern: pattern
        }, function(JSON_Data) {
            var products = JSON_Data[key];
            elementsInDatabase = JSON_Data.count;
            checkForEmpty(elementsInDatabase);
            createPaginationView(paginatorElementsOnPage, elementsInDatabase, activePaginatorNumber);
            $.each(products, function(index, element) {
                var product = createTableRow(element);
                tableBody.appendChild(product);
            });
        });

    }
    function highlightRow(node) {
        if (activeRow && activeRow !=node) {
            activeRow.classList.remove('active-row');
            activeRow.removeAttribute('order');
        }
        activeRow = node;
        activeRow.classList.add('active-row');
        activeRowField = activeRow.getAttribute('field');
        sortOrder = activeRow.getAttribute('order');
    }
    function delegateTableHeaderClick(event) {
        var target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === "THEAD") {
            return;
        } else if (target.tagName === 'TH' && (target.getAttribute('inactive') != 'false')) {
            filterbyfield = '';
            pattern = '';
            if (target.getAttribute('order') === 'ASC') {
                target.setAttribute('order', 'DESC');
            } else {
                target.setAttribute('order', 'ASC');
            }
            highlightRow(target);
            getProductsAjax();
        }
    }
    function onInputKeyDown(event) {
        if (event.keyCode === 13) {
            if (event.target.value) {
                if (activeRow) {
                    activeRow.classList.remove('active-row');
                    activeRow.removeAttribute('order');
                }
                activeRowField = '';
                sortOrder = '';
                filterbyfield = event.target.getAttribute('field');
                pattern = event.target.value;
                activePaginatorNumber =1;
                getProductsAjax();
            }
        } else if (event.keyCode === 8 || event.keyCode === 46) {
            if (!event.target.value) {
                activeRowField = '';
                sortOrder = '';
                filterbyfield = '';
                pattern = '';
                activePaginatorNumber =1;
                getProductsAjax();
            }
        }
    }
    function declareInputKeyDown() {
        var inputs = document.getElementsByClassName('filter-input');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].onkeyup = function (event) {
                onInputKeyDown(event);
            }
        }
    }
    function checkForEmpty(count) {
        if (count) {
            return;
        }

        else {
            var h2 = document.createElement('h2');
            h2.innerHTML = 'There is no products here';
            productsGrid.appendChild(h2);
        }
    }
    function checkOptions(options) {
        if (!options.dataUrl) {
            throw 'Wrong "dataUrl" exception';
        }
        if (!options.sortableColumns) {
            options.sortableColumns = [];
        }
        if (!options.filterableColumns) {
            options.filterableColumns = [];
        }
        if (!options.rowsPerPage) {
            options.rowsPerPage = 5;
        }
    }
}
