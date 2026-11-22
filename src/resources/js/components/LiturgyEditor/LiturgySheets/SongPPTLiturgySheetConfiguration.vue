<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -
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
    <LiturgySheetConfigurationForm
        :service="service"
        :sheet="sheet"
    >
        <TabHeaders>
            <TabHeader
                id="colorscheme"
                active="1"
                title="Farbschema"
            />
            <TabHeader
                id="layout"
                title="Layout"
            />
            <TabHeader
                id="content"
                title="Inhalte"
            />
            <TabHeader
                id="ads"
                title="Werbung"
            />
            <TabHeader
                id="format"
                title="Ausgabeformat"
            />
        </TabHeaders>
        <Tabs>
            <Tab
                id="colorscheme"
                active="1"
            >
                <input
                    type="hidden"
                    name="config[backgroundColor]"
                    v-model="myConfig.backgroundColor"
                />
                <FormSelectize
                    label="Hintergrundfarbe für Folien mit Text"
                    v-model="myConfig.backgroundColor"
                    :options="myColorOptions"
                />
                <input
                    type="hidden"
                    name="config[backgroundColorEmpty]"
                    v-model="myConfig.backgroundColorEmpty"
                />
                <FormSelectize
                    label="Hintergrundfarbe für Folien ohne Text"
                    v-model="myConfig.backgroundColorEmpty"
                    :options="myColorOptions"
                />
                <input
                    type="hidden"
                    name="config[textColor]"
                    v-model="myConfig.textColor"
                />
                <FormSelectize
                    label="Textfarbe"
                    v-model="myConfig.textColor"
                    :options="myColorOptions"
                />
                <input
                    type="hidden"
                    name="config[verticalAlignment]"
                    v-model="myConfig.verticalAlignment"
                />
            </Tab>
            <Tab id="layout">
                <FormSelectize
                    label="Vertikale Ausrichtung der Texte"
                    v-model="myConfig.verticalAlignment"
                    :options="myAlignmentOptions"
                />
                <FormInput
                    label="Schrifgröße"
                    type="number"
                    v-model="myConfig.fontSize"
                />
            </Tab>
            <Tab id="content">
                <FormSelectize
                    label="Folie zwischen den Elementen einfügen"
                    v-model="myConfig.slideBetweenElements"
                    name="config[slideBetweenElements]"
                    :options="[
                        { id: 'none', name: 'Keine' },
                        { id: 'empty', name: 'Leere Folie' },
                        { id: 'title', name: 'Titelfolie' },
                    ]"
                />
                <template v-if="myConfig.slideBetweenElements === 'title'">
                    <FormInput
                        label="Titel der Titelfolie"
                        v-model="myConfig.titleSlideTitle"
                        name="config[titleSlideTitle]"
                    />
                    <FormInput
                        label="Untertitel der Titelfolie"
                        v-model="myConfig.titleSlideSubtitle"
                        name="config[titleSlideSubtitle]"
                    />
                    <input
                        type="hidden"
                        name="config[titleSlideAttachmentId]"
                        v-model="myConfig.titleSlideAttachmentId"
                    />
                    <FormFileUploader
                        v-if="!titleSlideAttachments.length"
                        title="Titelfolie"
                        :single="true"
                        :upload-route="route('service.attach', service.slug)"
                        chunk-upload-route="/api/attachment-uploads"
                        :chunk-target="{
                            target_type: 'App\\Models\\Service',
                            target_id: service.id,
                        }"
                        :presets="[titleSlidePreset]"
                        cut="ppt-title-slide"
                        @update:model-value="setTitleSlideAttachment"
                    />
                    <AttachmentList
                        :model-value="titleSlideAttachments"
                        :prevent-empty-list-message="true"
                        delete-route-name="service.detach"
                        parent-type="service"
                        parent-route-key="slug"
                        :parent-object="service"
                        @update:model-value="setTitleSlideAttachment"
                    />
                </template>
                <FormCheck
                    label="Platzhalter für Jingle, Intro, ..."
                    v-model="myConfig.includeJingleAndIntro"
                    name="config[includeJingleAndIntro]"
                />
                <FormCheck
                    label="Mitwirkende anzeigen"
                    v-model="myConfig.includeCredits"
                    name="config[includeCredits]"
                />
                <hr />
                <FormCheck
                    label="Liederliste am Anfang"
                    v-model="myConfig.includeSongList"
                    name="config[includeSongList]"
                />
                <FormCheck
                    label="Hinweis auf Liederbuch vor jedem Lied"
                    v-model="myConfig.includeSongbookReference"
                    name="config[includeSongbookReference]"
                />
                <FormCheck
                    label="Wo möglich, Noten statt Text verwenden"
                    v-model="myConfig.renderMusic"
                    name="config[renderMusic]"
                />
                <FormCheck
                    label="QR-Code für digitales Liedblatt anzeigen"
                    v-model="myConfig.includeVirtualSongsheetQR"
                    name="config[includeVirtualSongsheetQR]"
                />
            </Tab>
            <Tab id="ads">
                <FormCheck
                    label="Veranstaltungswerbung am Anfang einfügen"
                    v-model="myConfig.includeAdLoopStart"
                    name="config[includeAdLoopStart]"
                />
                <FormCheck
                    label="Veranstaltungswerbung am Ende einfügen"
                    v-model="myConfig.includeAdLoopEnd"
                    name="config[includeAdLoopEnd]"
                />
                <FormSelectize
                    label="Veranstaltungswerbung bei folgenden Elementen einfügen"
                    multiple
                    :options="myItems"
                    v-model="myConfig.includeAdLoopElements"
                    name="config[includeAdLoopElements][]"
                />
                <hr />
                <FormSelectize
                    label="Veranstaltungen aus folgenden Kirchengemeinden einschließen"
                    :key="this.myCities.length"
                    multiple
                    v-model="myConfig.showAdsFromCities"
                    name="config[showAdsFromCities][]"
                    :options="myCities"
                />
                <hr />
                <FormInput
                    label="Werbefolien nach ___ Sekunden weiterschalten"
                    v-model="myConfig.adLoopDelay"
                    name="config[adLoopDelay]"
                    type="number"
                    min="0"
                    help="Bei 0 erfolgt keine automatische Weiterschaltung"
                />
            </Tab>
            <Tab id="format">
                <FormRadioGroup
                    v-model="myConfig.outputFormat"
                    label="Dateiformat"
                    name="config[outputFormat]"
                    :items="{
                        ppt: 'Microsoft PowerPoint (.pptx, .pptm)',
                        odp: 'OpenDocument Presentation (.odp)',
                    }"
                />
            </Tab>
        </Tabs>
    </LiturgySheetConfigurationForm>
</template>

<script>
import LiturgySheetConfigurationForm from './LiturgySheetConfigurationForm'
import FormCheck from '../../Ui/forms/FormCheck'
import FormRadioGroup from '../../Ui/forms/FormRadioGroup.vue'
import FormSelectize from '../../Ui/forms/FormSelectize'
import FormInput from '../../Ui/forms/FormInput'
import FormFileUploader from '../../Ui/forms/FormFileUploader.vue'
import AttachmentList from '../../Ui/elements/AttachmentList.vue'
import Tabs from '../../Ui/tabs/tabs'
import Tab from '../../Ui/tabs/tab'
import TabHeaders from '../../Ui/tabs/tabHeaders'
import TabHeader from '../../Ui/tabs/tabHeader'
/* global route */
export default {
    name: 'SongPPTLiturgySheetConfiguration',
    components: {
        TabHeader,
        TabHeaders,
        Tab,
        Tabs,
        FormInput,
        FormFileUploader,
        AttachmentList,
        FormRadioGroup,
        FormSelectize,
        FormCheck,
        LiturgySheetConfigurationForm,
    },
    props: ['service', 'sheet'],
    created() {
        this.$api()
            .get(route('api.cities.index'))
            .then((response) => {
                this.myCities = response.data
            })
    },
    data() {
        /** Keep attachment selection reactive without mutating the service prop. */
        const myAttachments = [...(this.service.attachments || [])]
        const myConfig = this.sheet.config
        if (!(myConfig.showAdsFromCities || []).length)
            myConfig.showAdsFromCities = [this.service.city_id]
        myConfig.adLoopDelay = parseInt(myConfig.adLoopDelay || '7')
        if (!myConfig.outputFormat) myConfig.outputFormat = 'ppt'
        if (!myConfig.slideBetweenElements) myConfig.slideBetweenElements = 'empty'

        const myItems = []
        if (!(myConfig.includeAdsLoopElements || []).length) myConfig.includeAdLoopElements = []
        this.service.liturgy_blocks.forEach((block) => {
            block.items.forEach((item) => {
                myItems.push({ id: item.id, name: item.title })
            })
        })

        return {
            myCities: [],
            myAttachments,
            myConfig,
            myItems,
            myColorOptions: [
                { id: 'FF043b04', name: 'Grün (für Greenscreen bei der Liveübertragung)' },
                { id: 'FF000000', name: 'Schwarz' },
                { id: 'FFFFFFFF', name: 'Weiß' },
            ],
            myAlignmentOptions: [
                { id: 't', name: 'Am oberen Rand ausrichten' },
                { id: 'b', name: 'Am unteren Rand ausrichten' },
                { id: 'ctr', name: 'Mittig ausrichten' },
                { id: 'auto', name: 'Automatisch ausrichten' },
            ],
        }
    },
    computed: {
        titleSlideAttachments() {
            return this.myAttachments.filter((attachment) => attachment.cut === 'ppt-title-slide')
        },
        titleSlidePreset() {
            return {
                title: 'Titelfolie (16:9)',
                cut: 'ppt-title-slide',
                width: 1920,
                height: 1080,
            }
        },
    },
    methods: {
        /** Store the uploaded title-slide attachment in the export configuration. */
        setTitleSlideAttachment(attachments) {
            /** The selected title-slide attachment, when one is present. */
            const attachment = (attachments || []).find((item) => item.cut === 'ppt-title-slide')
            if (!attachment) {
                this.myConfig.titleSlideAttachmentId = null
                this.myAttachments = attachments?.length
                    ? attachments
                    : this.myAttachments.filter((item) => item.cut !== 'ppt-title-slide')
                return
            }
            this.myConfig.titleSlideAttachmentId = attachment.id
            this.myAttachments = [
                ...this.myAttachments.filter((item) => item.cut !== 'ppt-title-slide'),
                attachment,
            ]
        },
    },
}
</script>

<style scoped></style>
