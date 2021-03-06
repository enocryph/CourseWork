$.fn.categoryAdmin = function(options) {
    checkOptions(options);
    var rootElement = this[0]; //root element for appending
    rootElement.setAttribute('tree-level', '2');
    var ulClassCSS = 'tree-list';
    var liClassCSS = 'tree-item';
    var liControlOpenCSS = 'tree-item-control-opened';
    var liControlCloseCSS = 'tree-item-control-closed';
    var dataUrl = options.dataUrl;
    var select = document.getElementById(options.selectKey);

    rootElement.onclick = function (event) {
        delegateClick(event);
    }

    buildCategoryTree(null, rootElement);

    function generateSplitter(level) {
        var splitter = '&nbsp;&nbsp;';
        var completeSplitter = '';
        for (var i = 0; i < level; i++) {
            completeSplitter += splitter;
        }
        return completeSplitter;
    }

    function buildCategoryTree(categoryId, parent) {
        $.getJSON(dataUrl + categoryId, function (JSON_Data) {
            var categoryList = createUl();
            $.each(JSON_Data, function (index, element) {
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
        var expandLevel = parseInt(treeLevel) + 1;
        var a = document.createElement('a');


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
        select.value = target.getAttribute('category_id');
        if (target.tagName !== "DIV" && target.tagName !== "UL") {
            var expand = target.getAttribute('expanded');
            var download = target.getAttribute('downloaded');
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

    function toggleClasses(target, firstClass, secondClass) {
        target.classList.toggle(firstClass);
        target.classList.toggle(secondClass);
        return target;
    }

    function checkOptions(options) {
        if (!options.dataUrl) {
            throw 'Wrong "dataUrl" exception';
        }
        if (!options.selectKey) {
            throw 'Wrong key'
        }
    }
}
