/**
 * Process Manager.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://github.com/dpfaffenbauer/ProcessManager/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS('pimcore.plugin.processmanager');

pimcore.plugin.processmanager = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return 'pimcore.plugin.processmanager';
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {

        var user = pimcore.globalmanager.get('user');

        if (user.isAllowed('process_manager')) {

            var exportMenu = new Ext.Action({
                text: t('processmanager'),
                iconCls: 'processmanager_nav_icon_processes',
                handler:this.openProcesses
            });

            if (layoutToolbar.extrasMenu === undefined) {
                var extrasMenu = new Ext.menu.Menu({
                    items: [],
                    shadow: false,
                    cls: "pimcore_navigation_flyout",
                    listeners: {
                        "show": function (e) {
                            Ext.get('pimcore_menu_extras').addCls('active');
                        },
                        "hide": function (e) {
                            Ext.get('pimcore_menu_extras').removeCls('active');
                        }
                    }
                });
                layoutToolbar.extrasMenu = extrasMenu;
                Ext.get("pimcore_menu_extras").on("mousedown", layoutToolbar.showSubMenu.bind(extrasMenu)).show();
            } else {
                layoutToolbar.extrasMenu.add({xtype: 'menuseparator'});
            }

            layoutToolbar.extrasMenu.add(exportMenu);

            this.trigger(document, 'processmanager.ready');
        }
    },

    openProcesses : function ()
    {
        try {
            pimcore.globalmanager.get('processmanager_definition_processes').activate();
        }
        catch (e) {
            pimcore.globalmanager.add('processmanager_definition_processes', new pimcore.plugin.processmanager.panel());
        }
    },

    trigger: function (target, event, detail) {
        return target.dispatchEvent(this.createEvent(event, true, true, detail));
    },

    createEvent: function (e, bubbles = true, cancelable = false, detail) {
        const event = document.createEvent('CustomEvent');
        event.initCustomEvent(e, bubbles, cancelable, detail);

        return event;
    }
});

var processmanagerPlugin = new pimcore.plugin.processmanager();
