<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -
  - Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
  -
  - Pfarrplaner is based on the Laravel framework (https://laravel.com).
  - This file may contain code created by Laravel's scaffolding functions.
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="liturgy-tree pb-4">
        <div class="row py-2 border-bottom mb-2">
            <div class="col-md-6">
            </div>
            <div class="col-md-6 text-end"></div>
        </div>
        <div v-for="(block,blockIndex) in blocks" class="liturgy-block">
            <div class="row" :ref="'block'+blockIndex" :key="'block'+blockIndex">
                <div class="col-11 liturgy-block-title">
                        <span class="mdi mdi-drag-horizontal handle me-1"
                              title="Klicken und ziehen, um die Position im Ablauf zu verändern"></span>
                    <span class="mdi mdi-chevron-right-circle" style="display: none;"></span> {{ block.title }}
                </div>
                <div class="col-1 text-end" v-if="editable">
                </div>
            </div>
            <div v-for="(item,itemIndex) in block.items" class="liturgy-item"
                 :data-block-index="blockIndex" :data-item-index="itemIndex">
                <div class="row item" :ref="'block'+blockIndex+'_item'+itemIndex"
                     title="Klicken, um zu bearbeiten.">
                    <div class="col-sm-3 item-title">
                                    <span class="fa data-type-icon handle me-1" :class="icons[item.data_type]"
                                          title="Klicken und ziehen, um die Position im Ablauf zu verändern"></span>
                        <span class="mdi mdi-chevron-right-circle"
                              style="display: none;"></span> {{ item.title }}
                    </div>
                    <div class="col-sm-4" v-if="item.data_type == 'sermon'">
                        <div v-if="myService.sermon === null">
                            <i>Für diesen Gottesdienst ist noch keine Predigt angelegt.</i>
                        </div>
                        <div v-else>
                                {{ myService.sermon.title }}<span
                                v-if="myService.sermon.subtitle">: {{ myService.sermon.subtitle }}</span>
                            <div v-if="myService.sermon.reference" class="text-sm text-muted">
                                {{ myService.sermon.reference }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4" v-else>{{ itemDescription(item) }}
                        <span v-if="item.data.needs_replacement" class="badge"
                              :class="dataReplacerClass(item)">
                                        <span class="mdi mdi-account" :title="dataReplacerTitle(item)"></span>
                                    </span>
                        <span v-if="(item.data_type=='song') && item.data.song && item.data.song.notation"
                              class="mdi mdi-music text-success"
                              title="Zu diesem Lied sind Noten vorhanden."/>
                    </div>
                    <div class="col-sm-2 responsible-list">
                        <div v-if="item.data.responsible.length > 0">
                                            <span class="badge bg-light" v-for="record in item.data.responsible"
                                                  v-html="displayResponsible(record)"/>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="row">
                            <item-starting-time class="col-6" :item="item" :service="service"/>
                            <item-text-stats class="col-6" :item="item" :service="service"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" v-if="blocks.length > 0">
            <div class="col-sm-7"></div>
            <div class="col-sm-2" style="border-top: solid 1px lightgray;">
                <small>Berechnetes Ende:</small>
            </div>
            <div class="col-sm-2">
                <div class="row">
                    <item-starting-time class="col-6" style="border-top: solid 1px lightgray;" :item="{id: -1}"
                                        :service="service"/>
                    <item-starting-time class="col-6" style="border-top: solid 1px lightgray;" :item="{id: -1}"
                                        :service="service" start="00:00"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LiturgyBlock from "../Elements/LiturgyBlock";
import DetailsPane from "./DetailsPane";
import Modal from "../../Ui/modals/Modal";
import FormSelectize from "../../Ui/forms/FormSelectize";
import ItemTextStats from "../Elements/ItemTextStats";
import ItemStartingTime from "../Elements/ItemStartingTime";
import NavButton from "../../Ui/buttons/NavButton";

export default {
    name: "LiturgyViewer",
    components: {
        NavButton,
        ItemStartingTime,
        ItemTextStats,
        FormSelectize,
        Modal,
        LiturgyBlock,
        DetailsPane,
    },
    props: {
        service: Object,
        sheets: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        },
        autoFocusBlock: {
            type: String,
            default: null,
        },
        autoFocusItem: {
            type: String,
            default: null,
        },
        ministries: {
            type: Object,
            default: [],
        },
        markers: {
            type: Object,
            default: null,
        }
    },
    /**
     * Load existing sources
     * @returns {Promise<void>}
     */
    async created() {
        axios.get(route('api.liturgy.sources', {
            api_token: this.apiToken,
            serviceId: this.service.id,
        })).then(response => {
            if (response.data) {
                this.sources = response.data;
                this.sourceSelectizeSettings.placeholder = 'Ablaufelemente importieren...';
                this.importFrom = -1;
            }
        });

        axios.get(route('liturgy.sermons', this.myService.slug)).then(response => this.sermons = response.data);
        axios.get(route('api.liturgy.text.list', {api_token: this.apiToken})).then(response => this.texts = response.data);
        axios.get(route('api.liturgy.song.select', {api_token: this.apiToken})).then(response => {
            this.songList = response.data;
        });
    },
    mounted() {
        if (this.autoFocusItem && this.autoFocusBlock) {
            var autoFocusBlock = parseInt(this.autoFocusBlock);
            var autoFocusItem = parseInt(this.autoFocusItem);
            var foundBlock = false;
            var foundItem = false;
            this.blocks.forEach(function (block, blockIndex) {
                block.items.forEach(function (item, itemIndex) {
                    if ((block.id == autoFocusBlock) && (item.id == autoFocusItem)) {
                        foundBlock = blockIndex;
                        foundItem = itemIndex;
                    }
                }, this);
            }, this);
            if ((foundBlock !== false) && (foundItem !== false)) this.focusItem(foundBlock, foundItem);
        } else {
            if (this.autoFocusBlock) {
                var autoFocusBlock = parseInt(this.autoFocusBlock);
                var foundBlock = false;
                this.blocks.forEach(function (block, blockIndex) {
                    if (block.id == autoFocusBlock) foundBlock = blockIndex;
                }, this);
                if (foundBlock !== false) this.focusBlock(foundBlock);
            }
        }
    },
    beforeUnmount() {
        // here we need to do some dirty checking and saving!
    },
    data() {
        let myService = this.service;
        if (undefined != myService.liturgy_blocks) {
            var myBlocks = myService.liturgy_blocks;
        } else {
            var myBlocks = [];
        }
        myBlocks.forEach(function (val, idx) {
            myBlocks[idx].data_type = 'block';
            myBlocks[idx].typeDescription = 'Abschnitt';
            myBlocks[idx].editing = false;
            myBlocks[idx].items.forEach(function (val2, idx2) {
                myBlocks[idx].items[idx2].editing = false;
                if (undefined == myBlocks[idx].items[idx2].data.responsible) myBlocks[idx].items[idx2].data.responsible = [];
            });
        });

        return {
            myService,
            apiToken: this.$page.props.currentUser.data.api_token,
            reloadingTree: false,
            treeState: Math.random().toString(36).substr(2, 9),
            icons: {
                freetext: 'mdi mdi-text',
                psalm: 'mdi mdi-hands-pray',
                reading: 'mdi mdi-book-open-variant',
                sermon: 'mdi mdi-microphone',
                song: 'mdi mdi-music',
            },
            blocks: myBlocks,
            focusedBlock: null,
            focusedItem: null,
            editable: true,
            importFrom: null,
            modalOpen: false,
            sermons: [],
            songList: [],
            texts: [],
            sources: [],
            sourceSelectizeSettings: {
                placeholder: 'Bitte warten, Quellen werden geladen...',
                valueField: 'id',
                labelField: 'name',
                searchField: ['name'],
                optgroupField: 'group',
                optgroupLabelField: 'groupName',
                optgroupValueField: 'groupName',
                optgroups: [{groupName: 'Vorlagen'}, {groupName: 'Gottesdienste'}],
            }
        }
    },
    methods: {
        addBlock() {
            axios.post(route('api.liturgy.block.store', {
                api_token: this.apiToken,
                service: this.service.id,
            }), {
                title: 'Abschnitt ' + (this.blocks.length + 1)
            }).then(response => {
                let block = response.data;
                if (undefined == block.items) block.items = [];
                block.data_type = 'block';
                block.typeDescription = 'Abschnitt';
                block.editing = false;
                let blockIndex = this.blocks.push(response.data);
                this.focusBlock(blockIndex - 1);
            });
        },
        deleteBlock(index) {
            axios.delete(route('api.liturgy.block.destroy', {
                api_token: this.apiToken,
                block: this.blocks[index].id
            })).then(response => {
                this.blocks.splice(index, 1);
            });
        },
        saveState() {
            var i = 0;
            this.blocks.forEach(function (block) {
                block.sortable = i++;
                var j = 0;
                block.items.forEach(function (item) {
                    item.sortable = j++;
                })
            })

            axios.post(route('api.liturgy.tree.save', {service: this.service.id, api_token: this.apiToken}), {
                blocks: this.blocks
            })
                .then(response => this.reloadTree(response.data));
        },
        addItem(blockIndex, type) {
            if (!this.editable) return false;
            var obj;
            switch (type) {
                case 'Freetext':
                    obj = {
                        title: 'Freier Text',
                        data_type: 'freetext',
                        data: {description: ''},
                    };
                    break;
                case 'Psalm':
                    obj = {
                        title: 'Psalmgebet',
                        data_type: 'psalm',
                    };
                    break;
                case 'Reading':
                    obj = {
                        title: 'Schriftlesung',
                        data_type: 'reading',
                        data: {reference: ''},
                    };
                    break;
                case 'Sermon':
                    obj = {
                        title: 'Predigt',
                        data_type: 'sermon',
                    };
                    break;
                case 'Song':
                    obj = {
                        title: 'Lied',
                        data_type: 'song',
                    };
                    break;
            }
            axios.post(route('api.liturgy.item.store', {
                block: this.blocks[blockIndex].id,
                api_token: this.apiToken
            }), obj)
                .then(response => {
                    let item = response.data.item;
                    if (item.data.length == 0) item.data = {};
                    if (undefined == item.data.responsible) item.data.responsible = [];
                    let itemIndex = this.blocks[blockIndex].items.push(item);
                    this.focusItem(blockIndex, itemIndex - 1);
                });
            //var index = this.blocks[blockIndex].items.push(obj);
        },
        deleteItem(blockIndex, itemIndex) {
            axios.delete(route('api.liturgy.item.destroy', {
                api_token: this.apiToken,
                item: this.blocks[blockIndex].items[itemIndex].id,
            })).then(response => {
                this.blocks[blockIndex].items.splice(itemIndex, 1);
            });
        },
        focusBlock(blockIndex) {
            if (!this.editable) return false;
            if (this.focusedBlock == blockIndex) {
                this.blocks[blockIndex].editing = false;
                this.focusOff();
            } else {
                this.focusOff();
                this.blocks[blockIndex].editing = true;
                this.focusedBlock = blockIndex;
                this.focusedItem = null;
                this.updateFocus(this.blocks[blockIndex]);
                this.scrollToRef('block' + blockIndex);
            }
        },
        focusItem(blockIndex, itemIndex) {
            if (!this.editable) return false;
            if ((this.focusedBlock == blockIndex) && (this.focusedItem == itemIndex)) {
                this.blocks[blockIndex].items[itemIndex].editing = false;
                this.focusOff();
            } else {
                this.focusOff();
                this.blocks[blockIndex].items[itemIndex].editing = true;
                this.focusedBlock = blockIndex;
                this.focusedItem = itemIndex;
                this.updateFocus(this.blocks[blockIndex].items[itemIndex]);
            }
        },
        focusOff() {
            this.focusedBlock = this.focusedItem = null;
            this.updateFocus(null);
        },
        itemDescription(item) {
            switch (item.data_type) {
                case 'freetext':
                    if (null === item.data.description) return '';
                    if (undefined === item.data.description) return '';
                    let s = item.data.description.replaceAll('<p>', '')
                        .replaceAll('</p>', "\r\n")
                        .replaceAll('<br>', "\n")
                        .replaceAll('<br />', "\n")
                        .replaceAll('<br/>', "\n");
                    return s.length > 40 ? s.substr(0, 40) + '...' : s;
                case 'psalm':
                    if (undefined == item.data.psalm) return '';
                    var title = item.data.psalm.title;
                    if (item.data.psalm.reference) {
                        title = item.data.psalm.reference + ' ' + title;
                    }
                    if (item.data.psalm.songbook_abbreviation) {
                        title = item.data.psalm.songbook_abbreviation + ' ' + title;
                    } else if (item.data.psalm.songbook) {
                        title = item.data.psalm.songbook + ' ' + title;
                    }
                    return title;
                case 'sermon':
                    return;
                case 'reading':
                    return item.data.reference;
                case 'song':
                    if (undefined == item.data.song) return '';
                    if (undefined == item.data.song.song) return '';
                    var title = item.data.song.song.title;
                    if (item.data.song.altEG) {
                        title = '(EG ' + item.data.song.altEG + ') ' + title;
                    }
                    if (item.data.song.reference) {
                        title = item.data.song.reference + ' ' + title;
                    }
                    if (item.data.song.code) {
                        title = item.data.song.code + ' ' + title;
                    } else if (item.data.song.songbook) {
                        title = item.data.song.songbook.name + ' ' + title;
                    }
                    if (item.data.verses) {
                        title = title + ', ' + item.data.verses;
                    }
                    return title;
            }
            return '';
        },
        updateFocus(object) {
            this.editable = (object === null);
            this.$emit('update-focus', this.focusedBlock, this.focusedItem, object);
        },
        reloadTree(data = null) {
            if (!data) return;
            if (data.service) this.myService = data.service;
            if (data.tree) {
                this.myService.liturgy_blocks = data.tree;
                this.blocks = data.tree;
            }

            for (const idx in this.blocks) {
                this.blocks[idx].data_type = 'block';
                this.blocks[idx].typeDescription = 'Abschnitt';
                this.blocks[idx].editing = false;
                for (const idx2 in this.blocks[idx].items) {
                    this.blocks[idx].items[idx2].editing = false;
                    if (undefined == this.blocks[idx].items[idx2].data.responsible) this.blocks[idx].items[idx2].data.responsible = [];
                }
            }

            this.reloadingTree = false;
            this.treeState = Math.random().toString(36).substr(2, 9);
        },
        save() {
            this.reloadingTree = true;
            axios.post(route('api.liturgy.tree.save', {
                service: this.service.id,
                api_token: this.apiToken
            }), {blocks: this.blocks})
                .then(response => this.reloadTree(response.data));
        },
        displayResponsible(record) {
            var title = '';
            if (typeof record != 'string') return;
            var tmp = record.split(':');
            if (tmp[0] == 'user') {
                this.myService.participants.forEach(function (person) {
                    if (person.id == tmp[1]) title = '<span class="mdi mdi-account-check"></span> ' + person.name;
                });
                return title;
            } else if (tmp[0] == 'ministry') {
                switch (tmp[1]) {
                    case 'pastors':
                        return '<span class="mdi mdi-account-multiple"></span> ' + this.$page.props.labels.pastor;
                    case 'organists':
                        return '<span class="mdi mdi-account-multiple"></span> ' + this.$page.props.labels.organist;
                    case 'sacristans':
                        return '<span class="mdi mdi-account-multiple"></span> ' + this.$page.props.labels.sacristan;
                }
                return "<span class=\"mdi mdi-account-multiple\"></span> " + tmp[1];
            } else {
                return '<span class="mdi mdi-account-question"></span> ' + tmp[1];
            }
        },
        importElements() {
            this.modalOpen = false;
            if (this.importFrom == -1) return;
            this.reloadingTree = true;
            axios.post(route('api.liturgy.tree.import', {
                api_token: this.apiToken,
                service: this.service.id,
                source: this.importFrom,
            })).then(response => {
                this.reloadTree(response.data);
            });
        },
        dataReplacerTitle(item) {
            if (!item.data.needs_replacement) return '';
            var t = 'Dieses Element wird mit Hilfe von persönlichen Daten ';
            var error = '';
            var replacerObject = null;
            this.service[item.data.needs_replacement + 's'].forEach(obj => {
                if (obj.id == item.data.replacement) replacerObject = obj;
            })
            if (null === replacerObject) item.data.replacement = null;
            switch (item.data.needs_replacement) {
                case 'funeral':
                    t += 'für eine Bestattung';
                    if (!item.data.replacement) {
                        error = 'Es ist noch keine Bestattung ausgewählt!';
                        if (this.myService.funerals.length == 0) error += ' Dem Gottesdienst sind keine Bestattungen zugeordnet!';
                    } else {
                        t += ' (' + replacerObject.buried_name + ')';
                    }
                    break;
                case 'baptism':
                    t += 'für eine Taufe';
                    if (!item.data.replacement) {
                        3
                        error = 'Es ist noch keine Taufe ausgewählt!';
                        if (this.myService.baptisms.length == 0) error += ' Dem Gottesdienst sind keine Taufen zugeordnet!';
                    } else {
                        t += ' (' + replacerObject.candidate_name + ')';
                    }
                    break;
                case 'wedding':
                    t += 'für eine Trauung';
                    if (!item.data.replacement) {
                        error = 'Es ist noch keine Trauung ausgewählt!';
                        if (this.myService.weddings.length == 0) error += ' Dem Gottesdienst sind keine Trauungen zugeordnet!';
                    } else {
                        t += ' (' + replacerObject.spouse1_name + ' / ' + replacerObject.spouse2_name + ')';
                    }
                    break;
            }
            ;
            t += ' angepasst.' + (error ? ' ' + error : '');
            return (t)
        },
        dataReplacerClass(item) {
            if (!item.data.needs_replacement) return '';
            if (!item.data.replacement) return 'badge-danger';
            return 'badge-success';
        },
        scrollToRef(refId) {
            this.$nextTick(function () {
                let el = this.$refs[refId].$el;
                if (undefined == el) return;
                window.scrollTo(el.offsetLeft, el.offsetTop);
            });
        },
        cancelEditing(item, blockIndex, itemIndex = null) {
            if (undefined == item.data.responsible) item.data.responsible = [];
            if (null !== itemIndex) {
                this.blocks[blockIndex].items[itemIndex] = item;
                this.blocks[blockIndex].items[itemIndex].editing = false;
            } else {
                this.blocks[blockIndex] = item;
                this.blocks[blockIndex].editing = false;
            }
            this.treeState = Math.random().toString(36).substr(2, 9);
            this.$forceUpdate();
            this.focusOff();
        }
    },
    provide() {
        const lists = {};

        Object.defineProperty(lists, 'songs', {
            enumerable: true,
            get: () => this.songList,
        });
        Object.defineProperty(lists, 'texts', {
            enumerable: true,
            get: () => this.texts,
        });
        Object.defineProperty(lists, 'sermons', {
            enumerable: true,
            get: () => this.sermons,
        });
        Object.defineProperty(lists, 'ministries', {
            enumerable: true,
            get: () => this.ministries,
        });
        return {
            lists,
        }
    }
}
</script>

<style scoped>

.tree-loader {
    margin-top: 0;
    font-size: 6em;
    padding-top: 25vh;
    text-align: center;
}

.liturgy-block {
    padding: 3px 5px;
    margin: 5px;
}

.liturgy-block.focused {
    box-shadow: 0 0 5px rgba(81, 203, 238, 1);
    border: 1px solid rgba(81, 203, 238, 1);
}

.liturgy-blocks-list .liturgy-block:first-child {
    border-top: 0;
}


.liturgy-items-list {
    min-height: 10px;
}

.liturgy-block-title {
    font-weight: bold;
    font-size: 1.4em;
    color: rgb(145, 45, 125);
}

.liturgy-item {
    border-top: dotted 1px gray;
    padding: 3px 0;
    margin: 3px 0px;
}

.liturgy-item.focused .item-title {
    padding-left: 13px;
    font-weight: bold;
}

.liturgy-item.focused .item-title span.fa,
.liturgy-block.focused .liturgy-block-title span.fa {
    display: inline !important;
}

.liturgy-item.focused {
    box-shadow: 0 0 5px rgba(81, 203, 238, 1);
    border: 1px solid rgba(81, 203, 238, 1);
}


.liturgy-items-list .liturgy-item:first-child {
    border-top: 0;
}

.responsible-list {
    color: gray;
}

.source-select {
    text-align: left;
}

.ghost-item {

}


.item .handle {
    color: gray;
}

.data-type-icon {
    color: gray;
}

.row.item:hover {
    background-color: rgb(248, 249, 250);
}

</style>
