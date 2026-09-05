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
        <div v-if="reloadingTree" class="tree-loader">
            <span class="mdi mdi-spin mdi-loading"></span>
        </div>
        <div v-else class="liturgy-tree__surface">
            <div v-if="editable && !blocks.length" class="liturgy-tree__empty">
                <div class="liturgy-tree__empty-copy">
                    Noch keine Abschnitte vorhanden.
                </div>
                <button class="btn btn-primary" @click.prevent.stop="addBlock()">
                    <span class="mdi mdi-format-section me-1"></span>
                    Abschnitt einfügen
                </button>
            </div>
            <draggable :list="blocks" item-key="id" group="blocks" v-bind:class="{ghostClass: 'ghost-block'}"
                       class="liturgy-blocks-list" :key="treeState+blocks.length"
                       @start="focusOff" @end="saveState" :disabled="!editable" handle=".handle">
              <template #item="{ element: block, index: blockIndex }">
                <div class="liturgy-block-shell">
                    <div v-if="editable" class="insert-zone insert-zone--section">
                        <div class="insert-zone__line"></div>
                        <button class="btn btn-sm btn-outline-secondary insert-zone__section-button"
                                @click.prevent.stop="addBlock(blockIndex)">
                            <span class="mdi mdi-format-section me-md-1"></span>
                            <span class="d-none d-md-inline">Abschnitt hier einfügen</span>
                        </button>
                    </div>
                    <div class="liturgy-block"
                         :class="{focused: (focusedBlock == blockIndex) && (focusedItem == null)}"
                         @click="focusBlock(blockIndex)">
                        <div class="row align-items-center pe-2" :ref="'block'+blockIndex" :key="'block'+blockIndex">
                            <div class="col-10 liturgy-block-title">
                            <span class="mdi mdi-drag-horizontal handle me-1"
                                  title="Klicken und ziehen, um die Position im Ablauf zu verändern"></span>
                                <span class="mdi mdi-chevron-right-circle" style="display: none;"></span> {{ block.title }}
                            </div>
                            <div class="col-2 text-end pe-2" v-if="editable">
                                <button @click.stop="deleteBlock(blockIndex)" class="btn btn-sm btn-danger liturgy-block__delete-button me-2"
                                        title="Abschnitt löschen">
                                    <span class="mdi mdi-delete"></span>
                                </button>
                            </div>
                        </div>
                        <details-pane v-if="block.editing == true" :service="service" :element="block"
                                      @unfocus="cancelEditing($event, blockIndex)"
                                      :agenda-mode="agendaMode" :markers="markers"/>
                        <div v-if="editable" class="insert-zone insert-zone--block">
                            <div class="insert-zone__line"></div>
                            <liturgy-insert-menu label="Am Anfang einfügen" @insert="addItem(blockIndex, $event, 0)"/>
                        </div>

                        <draggable :list="block.items" item-key="id" group="items" class="liturgy-items-list" handle=".handle"
                                   v-bind:class="{ghostClass: 'ghost-item'}" @start="focusOff" @end="saveState"
                                   :disabled="!editable">
                          <template #item="{ element: item, index: itemIndex }">
                            <div class="liturgy-item-shell">
                                <div class="liturgy-item"
                                     @click.stop="focusItem(blockIndex, itemIndex)"
                                     :class="{focused: (focusedBlock == blockIndex) && (focusedItem == itemIndex)}"
                                     :data-block-index="blockIndex" :data-item-index="itemIndex">
                                    <div class="row item align-items-start" :ref="'block'+blockIndex+'_item'+itemIndex"
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
                                                <inertia-link :href="route('sermon.editor', {sermon: myService.sermon.id})"
                                                              @click.stop="" title="Hier klicken, um die Predigt zu bearbeiten">
                                                    {{ myService.sermon.title }}<span
                                                    v-if="myService.sermon.subtitle">: {{ myService.sermon.subtitle }}</span>
                                                </inertia-link>
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
                                                    <span class="badge bg-light me-1" v-for="record in item.data.responsible"
                                                          v-html="displayResponsible(record)"/>
                                            </div>
                                            <div v-else>
                                                <div v-if="editable">
                                                    <span class="mdi mdi-account-multiple"></span> Hier klicken, um
                                                    Verantwortliche
                                                    auszuwählen.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="row" v-if="!agendaMode">
                                                <item-starting-time class="col-6" :item="item" :service="service"/>
                                                <item-text-stats class="col-6" :item="item" :service="service"/>
                                            </div>
                                        </div>
                                        <div class="col-1 text-end" v-if="editable">
                                            <button @click.stop="deleteItem(blockIndex, itemIndex)"
                                                    class="btn btn-sm btn-danger" title="Element löschen">
                                                <span class="mdi mdi-delete"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <details-pane v-if="item.editing == true" :service="service" :element="item"
                                              :key="treeState+blockIndex+'_'+itemIndex+'_'+(item.editing ? 1 : 2)"
                                              @unfocus="cancelEditing($event, blockIndex, itemIndex)"
                                              :agenda-mode="agendaMode" :markers="markers"/>
                                <div v-if="editable" class="insert-zone">
                                    <div class="insert-zone__line"></div>
                                    <liturgy-insert-menu @insert="addItem(blockIndex, $event, itemIndex + 1)"/>
                                </div>
                            </div>
                          </template>
                        </draggable>
                        <div v-if="editable && !block.items.length" class="liturgy-block__empty">
                            <div class="text-muted mb-2">Dieser Abschnitt ist noch leer.</div>
                            <liturgy-insert-menu label="Element einfügen" @insert="addItem(blockIndex, $event, 0)"/>
                        </div>
                    </div>
                </div>
              </template>
            </draggable>
            <div v-if="editable && blocks.length" class="insert-zone insert-zone--section insert-zone--section-end">
                <div class="insert-zone__line"></div>
                <button class="btn btn-sm btn-outline-secondary insert-zone__section-button"
                        @click.prevent.stop="addBlock(blocks.length)">
                    <span class="mdi mdi-format-section me-md-1"></span>
                    <span class="d-none d-md-inline">Abschnitt hier einfügen</span>
                </button>
            </div>
            <div class="row" v-if="blocks.length > 0 && !agendaMode">
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
            <modal title="Elemente importieren" v-if="modalOpen" min-height="50vh"
                   @close="importElements" @cancel="modalOpen = false;"
                   close-button-label="Importieren" cancel-button-label="Abbrechen" max-width="800">
                <div v-if="importFrom != null">
                    <form-selectize :options="sources" v-model="importFrom" :settings="sourceSelectizeSettings"/>
                    <div v-if="selectedSource && selectedSource.group === 'Vorlagen'" class="mt-2">
                        <div v-if="selectedSource.description">{{ selectedSource.description }}</div>
                        <small v-if="selectedSource.source" class="text-muted">{{ selectedSource.source }}</small>
                    </div>
                </div>
                <div v-else class="text-align: right; width: 100%; color: darkgray;">
                    Importmöglichquellen werden geladen... <span class="mdi mdi-spin mdi-loading"></span>
                </div>
            </modal>
        </div>
    </div>
</template>

<script>
import draggable from 'vuedraggable'
import LiturgyBlock from "../Elements/LiturgyBlock";
import DetailsPane from "./DetailsPane";
import Modal from "../../Ui/modals/Modal";
import FormSelectize from "../../Ui/forms/FormSelectize";
import ItemTextStats from "../Elements/ItemTextStats";
import ItemStartingTime from "../Elements/ItemStartingTime";
import NavButton from "../../Ui/buttons/NavButton";
import LiturgyInsertMenu from "../Elements/LiturgyInsertMenu.vue";

export default {
    name: "LiturgyTree",
    components: {
        NavButton,
        ItemStartingTime,
        ItemTextStats,
        FormSelectize,
        Modal,
        LiturgyBlock,
        DetailsPane,
        draggable,
        LiturgyInsertMenu,
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
        this.$api().get(route('api.liturgy.sources', {
            serviceId: this.service.id,
        })).then(response => {
            if (response.data) {
                this.sources = response.data;
                this.sourceSelectizeSettings.placeholder = 'Ablaufelemente importieren...';
                this.importFrom = -1;
            }
        });

        axios.get(route('liturgy.sermons', this.myService.slug)).then(response => this.sermons = response.data);
        this.$api().get(route('api.liturgy.text.list')).then(response => this.texts = response.data);
        this.$api().get(route('api.liturgy.song.select')).then(response => {
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
    computed: {
        selectedSource() {
            if (!this.importFrom || !this.sources.length) return null;
            return this.sources.find(s => s.id == this.importFrom) || null;
        }
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
        openImportModal() {
            this.modalOpen = true;
        },
        addBlock(insertIndex = null) {
            this.$api().post(route('api.liturgy.block.store', {
                service: this.service.id,
            }), {
                title: 'Abschnitt ' + (this.blocks.length + 1)
            }).then(response => {
                let block = response.data;
                if (undefined == block.items) block.items = [];
                block.data_type = 'block';
                block.typeDescription = 'Abschnitt';
                block.editing = false;
                if ((insertIndex === null) || (insertIndex >= this.blocks.length)) {
                    let blockIndex = this.blocks.push(response.data);
                    this.focusBlock(blockIndex - 1);
                    return;
                }

                this.blocks.splice(insertIndex, 0, block);
                this.focusBlock(insertIndex);
                this.saveState(false);
            });
        },
        deleteBlock(index) {
            this.$api().delete(route('api.liturgy.block.destroy', {
                block: this.blocks[index].id
            })).then(response => {
                this.blocks.splice(index, 1);
            });
        },
        saveState(reload = true) {
            var i = 0;
            this.blocks.forEach(function (block) {
                block.sortable = i++;
                var j = 0;
                block.items.forEach(function (item) {
                    item.sortable = j++;
                })
            })

            this.$api().post(route('api.liturgy.tree.save', {service: this.service.id}), {
                blocks: this.blocks
            })
                .then(response => {
                    if (reload) this.reloadTree(response.data);
                });
        },
        addItem(blockIndex, type, insertIndex = null) {
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
            this.$api().post(route('api.liturgy.item.store', {
                block: this.blocks[blockIndex].id,
            }), obj)
                .then(response => {
                    let item = response.data.item;
                    if (item.data.length == 0) item.data = {};
                    if (undefined == item.data.responsible) item.data.responsible = [];
                    if ((insertIndex === null) || (insertIndex >= this.blocks[blockIndex].items.length)) {
                        let itemIndex = this.blocks[blockIndex].items.push(item);
                        this.focusItem(blockIndex, itemIndex - 1);
                        return;
                    }

                    this.blocks[blockIndex].items.splice(insertIndex, 0, item);
                    this.focusItem(blockIndex, insertIndex);
                    this.saveState(false);
                });
        },
        deleteItem(blockIndex, itemIndex) {
            this.$api().delete(route('api.liturgy.item.destroy', {
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
            this.$api().post(route('api.liturgy.tree.save', {
                service: this.service.id,
            }), {blocks: this.blocks})
                .then(response => this.reloadTree(response.data));
        },
        displayResponsible(record) {
            let title = '';
            let tmp = [];
            if (typeof record != 'string') return;
            if (record.includes(':')) {
                tmp = record.split(':');
            } else {
                tmp = ['free', record];
            }
            if (tmp[0] == 'user') {
                this.myService.participants.forEach(function (person) {
                    if (person.id == tmp[1]) title = '<span class="mdi mdi-account-check"></span> ' + person.name;
                });
                return title;
            } else if (tmp[0] == 'ministry') {
                switch (tmp[1]) {
                    case 'pastors':
                        return '<span class="mdi mdi-account-multiple"></span> '+this.$page.props.labels.pastor;
                    case 'organists':
                        return '<span class="mdi mdi-account-multiple"></span> '+this.$page.props.labels.organist;
                    case 'sacristans':
                        return '<span class="mdi mdi-account-multiple"></span> '+this.$page.props.labels.sacristan;
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
            this.$api().post(route('api.liturgy.tree.import', {
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
            if (item.data && undefined == item.data.responsible) item.data.responsible = [];
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
    watch: {
        blocks: {
            handler(blocks) {
                this.$emit('update-block-count', blocks.length);
            },
            immediate: true,
        },
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

.liturgy-tree__surface {
    padding: 1rem 1rem 1.25rem;
    background: rgba(var(--bs-white-rgb), 0.95);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
    border-radius: 1rem;
}

.liturgy-tree__empty {
    padding: 1rem 0 1.4rem;
    text-align: center;
}

.liturgy-tree__empty-copy {
    margin-bottom: 0.65rem;
    color: var(--bs-secondary-color);
}

.liturgy-block-shell + .liturgy-block-shell {
    margin-top: 0.1rem;
}

.liturgy-block {
    border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
    border-radius: 0.9rem;
    padding: 0.65rem 0.8rem;
    margin: 0 0 0.85rem;
    background: rgba(var(--bs-white-rgb), 0.98);
    cursor: pointer;
    transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

.liturgy-block.focused {
    box-shadow: 0 0.8rem 2rem rgba(var(--bs-primary-rgb), 0.12);
    border-color: rgba(var(--bs-primary-rgb), 0.24);
    transform: translateY(-1px);
}

.liturgy-items-list {
    min-height: 10px;
}

.liturgy-block-title {
    font-weight: bold;
    font-size: 1.15rem;
    color: rgb(145, 45, 125);
}

.liturgy-block__delete-button {
    margin-right: 0.35rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.12s ease;
}

.liturgy-block__empty {
    padding: 0.6rem 0 0.15rem;
    text-align: center;
}

.liturgy-item {
    padding: 0.45rem 0.65rem;
    margin: 0;
    cursor: pointer;
    border: 1px solid transparent;
    border-radius: 0.8rem;
    background: rgba(var(--bs-primary-rgb), 0.02);
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
    box-shadow: 0 0.8rem 1.6rem rgba(var(--bs-primary-rgb), 0.08);
    border-color: rgba(var(--bs-primary-rgb), 0.24);
}

.liturgy-item-shell + .liturgy-item-shell {
    margin-top: 0.15rem;
}

.responsible-list {
    color: gray;
}

.insert-zone {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 0;
    margin: 0;
    opacity: 0;
    pointer-events: none;
    overflow: visible;
    transition: opacity 0.12s ease;
}

.insert-zone--block {
    margin-top: 0.15rem;
}

.insert-zone--section {
    margin-bottom: 0.15rem;
}

.insert-zone--section-end {
    height: 2rem;
    margin-top: 0.15rem;
}

.insert-zone__line {
    position: absolute;
    inset: 50% 0 auto;
    border-top: 1px dashed rgba(var(--bs-primary-rgb), 0.2);
    transform: translateY(-50%);
}

.insert-zone :deep(.liturgy-insert-menu) {
    position: relative;
    z-index: 1;
    transform: scale(0.96);
    transition: transform 0.12s ease;
}

.insert-zone__section-button {
    position: relative;
    z-index: 1;
    border-radius: 999px;
    background: rgba(var(--bs-white-rgb), 0.96);
    transform: scale(0.96);
    transition: transform 0.12s ease, background-color 0.12s ease, border-color 0.12s ease, color 0.12s ease;
}

.insert-zone__section-button:hover,
.insert-zone__section-button:focus {
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-color: rgba(var(--bs-primary-rgb), 0.28);
    color: var(--bs-primary);
}

.liturgy-block:hover > .insert-zone,
.liturgy-block-shell:hover > .insert-zone,
.liturgy-item-shell:hover > .insert-zone,
.liturgy-block:focus-within > .insert-zone,
.liturgy-block-shell:focus-within > .insert-zone,
.liturgy-item-shell:focus-within > .insert-zone {
    opacity: 1;
    pointer-events: auto;
}

.liturgy-tree__surface:hover > .insert-zone--section-end,
.liturgy-tree__surface:focus-within > .insert-zone--section-end {
    opacity: 1;
    pointer-events: auto;
}

.liturgy-block:hover > .insert-zone :deep(.liturgy-insert-menu),
.liturgy-block-shell:hover > .insert-zone .insert-zone__section-button,
.liturgy-item-shell:hover > .insert-zone :deep(.liturgy-insert-menu),
.liturgy-block:focus-within > .insert-zone :deep(.liturgy-insert-menu),
.liturgy-block-shell:focus-within > .insert-zone .insert-zone__section-button,
.liturgy-item-shell:focus-within > .insert-zone :deep(.liturgy-insert-menu) {
    transform: scale(1);
}

.liturgy-tree__surface:hover > .insert-zone--section-end .insert-zone__section-button,
.liturgy-tree__surface:focus-within > .insert-zone--section-end .insert-zone__section-button {
    transform: scale(1);
}

.liturgy-block:hover .liturgy-block__delete-button,
.liturgy-block:focus-within .liturgy-block__delete-button,
.liturgy-item:hover .btn-danger,
.liturgy-item:focus-within .btn-danger {
    opacity: 1;
    pointer-events: auto;
}

.liturgy-item .btn-danger {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.12s ease;
}

.source-select {
    text-align: left;
}

.ghost-item {

}

.handle {
    cursor: move;
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
