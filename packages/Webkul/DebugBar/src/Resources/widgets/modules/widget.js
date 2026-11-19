(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying all modules
     *
     * Options:
     *  - data
     */
    class ModulesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('modules');
        }

        render() {
            this.list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer(li, module) {
                const name = document.createElement('strong');
                name.classList.add(csscls('name'));
                name.textContent = module.name;
                li.append(name);

                for (const [key, title] of [['queries', 'Queries'], ['views', 'Views'], ['models', 'Models']]) {
                    const count = document.createElement('span');
                    count.setAttribute('title', title);
                    count.classList.add(csscls(key));
                    count.textContent = module[key].length;
                    li.append(count);
                }

                const table = document.createElement('table');
                table.classList.add(csscls('params'));
                table.innerHTML = '<thead><tr><th colspan="2">Details</th></tr></thead>';
                const tbody = document.createElement('tbody');
                table.append(tbody);

                const addRow = (label, items, renderItem) => {
                    if (!items || !items.length) {
                        return;
                    }

                    const row = document.createElement('tr');
                    const nameTd = document.createElement('td');
                    nameTd.className = csscls('name');
                    nameTd.textContent = label;

                    const valueTd = document.createElement('td');
                    valueTd.className = csscls('value');

                    const list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer: renderItem });
                    list.set('data', items);
                    valueTd.append(list.el);

                    row.append(nameTd, valueTd);
                    tbody.append(row);
                };

                addRow('Models', module.models, (item, model) => {
                    item.textContent = model;
                });

                addRow('Views', module.views, (item, view) => {
                    item.textContent = view;
                });

                addRow('Queries', module.queries, (item, query) => {
                    const sql = document.createElement('code');
                    sql.classList.add(csscls('sql'));
                    sql.innerHTML = PhpDebugBar.Widgets.highlight(query.sql, 'sql');
                    item.append(sql);

                    for (const [key, title, value] of [['database', 'Connection', query.connection], ['duration', 'Duration', query.duration_str]]) {
                        const meta = document.createElement('span');
                        meta.setAttribute('title', title);
                        meta.classList.add(csscls(key));
                        meta.textContent = value;
                        item.append(meta);
                    }
                });

                table.hidden = true;
                li.append(table);
                li.style.cursor = 'pointer';
                li.addEventListener('click', (event) => {
                    if (window.getSelection().type === 'Range' || table.contains(event.target)) {
                        return;
                    }

                    table.hidden = !table.hidden;
                });
            } });

            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                this.list.set('data', data.modules);
            });
        }
    }

    PhpDebugBar.Widgets.ModulesWidget = ModulesWidget;
})();
