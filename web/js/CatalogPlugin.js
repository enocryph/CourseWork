
$.fn.catalogBuilder = function(options) {

    var rootElement = this[0]; //root element for appending
    rootElement.setAttribute('tree-level', '2');
    var productsGrid = document.getElementById('products-grid');

    var productViewLink = 'http://127.0.0.1:8000/catalog/product/';
    var imagesStorageLink = 'http://127.0.0.1:8000/uploads/images/';
    var ulClassCSS = 'tree-list';
    var liClassCSS = 'tree-item';
    var liControlOpenCSS = 'tree-item-control-opened';
    var liControlCloseCSS = 'tree-item-control-closed';
    var ajaxProductLink = 'ajax/products';
    var ajaxCategoryLink = 'ajax/category/';

    var activeLi; //variable for delegation

    var paginationWrapper = document.getElementById('pagination-wrapper');
    var paginationUlCSS = 'btn-group';
    var paginationLiCSS = 'btn-group__item'

    var activePaginator;
    var elementsInDatabase;

    var paginatorElementsOnPage = options.perPage;
    var activeCategory = options.categoryID;
    var activePaginatorNumber = options.page;


    var paginationButtonsCount;

    rootElement.onclick = function (event) {
        delegateClick(event);
    }
    paginationWrapper.onclick = function (event) {
        delegatePaginationClick(event);
    }

    //initial requests
    buildCategoryTree(null, rootElement);
    getProductsAjax();

    function checkForEmpty(count) {
        if (count) {
            return;
        }
        else {
            var h2 = document.createElement('h2');
            h2.innerHTML = 'There is no products here';
            h2.classList.add('text-center');
            productsGrid.appendChild(h2);
        }
    }
    function generateSplitter(level) {
        var splitter = '&nbsp;&nbsp;';
        var completeSplitter = '';
        for (var i = 0; i<level; i++) {
            completeSplitter += splitter;
        }
        return completeSplitter;
    }
    function createProductView(element) {
        var container = document.createElement('div');
        var thumbnail = document.createElement('div');
        var captionContainer = document.createElement('div');
        var caption = document.createElement('h4');
        var productLink = document.createElement('a');
        var productImage = document.createElement('img');
        var productCaptionSpan = document.createElement('span');

        thumbnail.setAttribute('class', 'thumbnail');
        container.setAttribute('class', 'item col-xs-12 col-md-6 col-lg-4');
        captionContainer.setAttribute('class', 'caption');
        caption.setAttribute('class', 'group inner list-group-item-heading text-center product-caption');

        productLink.setAttribute('href', productViewLink+element.id);
        productLink.setAttribute('class', "product-link");

        productImage.setAttribute('src', imagesStorageLink+element.image);
        productImage.setAttribute('class', "group list-group-image product-image");

        productCaptionSpan.setAttribute('class', 'product-caption-span');
        productCaptionSpan.innerHTML = element.name;

        caption.appendChild(productCaptionSpan);
        captionContainer.appendChild(caption);
        thumbnail.appendChild(productImage);
        thumbnail.appendChild(caption);
        productLink.appendChild(thumbnail);
        container.appendChild(productLink);

        return container;
    }
    function getProductsAjax() {
        deleteChilds(productsGrid);
        history.replaceState(null, null, '/catalog/?category='+activeCategory+'&page='+activePaginatorNumber);
        $.getJSON(ajaxProductLink, {
            category: activeCategory,
            page: activePaginatorNumber,
            perpage: paginatorElementsOnPage
        }, function(JSON_Data) {
            var products = JSON_Data.products;
            elementsInDatabase = JSON_Data.count;
            checkForEmpty(elementsInDatabase);
            createPaginationView(paginatorElementsOnPage, elementsInDatabase, activePaginatorNumber);
            $.each(products, function(index, element) {
                var product = createProductView(element);
                productsGrid.appendChild(product);
            });
        });

    }
    function deleteChilds(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
        return element;
    }
    function buildCategoryTree(categoryId, parent) {
        $.getJSON(ajaxCategoryLink + categoryId, function(JSON_Data) {
            var categoryList = createUl();
            $.each(JSON_Data, function(index, element) {
                var categoryListItem = createLi(element.children, element.id, element.title,
                    parent.getAttribute('tree-level'));

                categoryList.appendChild(categoryListItem);
            });
            parent.appendChild(categoryList);
        })
    }
    function createUl() {
        var ul = document.createElement('ul');
        ul.setAttribute('class', ulClassCSS);
        return ul;
    }
    function createLi(expandable, categoryID, categoryTitle, treeLevel) {

        var li = document.createElement('li');
        var expandLevel = parseInt(treeLevel)+1;

        li.setAttribute('expanded', 'false');
        li.setAttribute('downloaded', 'false');
        li.setAttribute('category_id', categoryID);
        li.setAttribute('tree-level', expandLevel.toString());
        li.classList.add(liClassCSS);
        li.innerHTML = generateSplitter(treeLevel) + categoryTitle + generateSplitter(2);

        if (expandable === false) {
            li.setAttribute('childs', 'false');
        } else if (expandable === true) {
            li.setAttribute('childs', 'true');
            li.classList.add(liControlCloseCSS);
        }

        return li;
    }
    function delegateClick(event) {
        var target = event.target;

        if (target.tagName !== "DIV" && target.tagName !== "UL") {
            activePaginatorNumber = 1;
            var expand = target.getAttribute('expanded');
            var download = target.getAttribute('downloaded');
            sendProductsRequest(target);
            if (target.getAttribute('childs') === 'false') {
                return;
            }

            if (expand === 'false' && download === 'false') {
                //if rolled up and not downloaded
                target.setAttribute('expanded', 'true');
                target.setAttribute('downloaded', 'true');
                buildCategoryTree(target.getAttribute('category_id'), event.target);
                toggleClasses(target, liControlCloseCSS, liControlOpenCSS);
            } else if (expand === 'true' && download === 'true') {
                //if expanded and downloaded
                target.setAttribute('expanded', 'false');
                (target.getElementsByTagName('ul')[0]).classList.add('inactive');
                toggleClasses(target, liControlCloseCSS, liControlOpenCSS);
            } else if (expand === 'false' && download === 'true') {
                //if rolled up and downloaded
                target.setAttribute('expanded', 'true');
                (target.getElementsByTagName('ul')[0]).classList.remove('inactive');
                toggleClasses(target, liControlCloseCSS, liControlOpenCSS);
            }

        }
    }
    function toggleClasses(target ,firstClass, secondClass) {
        target.classList.toggle(firstClass);
        target.classList.toggle(secondClass);
        return target;
    }

    function sendProductsRequest(node) {
        if (activeLi === node) {
            return;
        } else {

            if (activeLi) {
                activeLi.classList.remove('tree-item-active');
            }
            activeLi = node;
            activeLi.classList.add('tree-item-active');
            activeCategory = activeLi.getAttribute('category_id');
            //ajax request here!
            getProductsAjax();

        }
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
}