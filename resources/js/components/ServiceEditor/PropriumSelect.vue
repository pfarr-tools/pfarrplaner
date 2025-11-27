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

<script>
import FormSelectize from "../Ui/forms/FormSelectize.vue";

export default {
    name: "PropriumSelect",
    components: {FormSelectize},
    props: ['value', 'label'],
    data() {
        const codes = {
            "1ADV": "1. Advent",
            "1NCF": "1. So. n. Christfest",
            "1TRI": "1. So. n. Trinitatis",
            "1EPI": "1. So. nach Epiphanias",
            "10TR": "10. So. n. Trinitatis (Israelsonntag)",
            "11TR": "11. So. n. Trinitatis",
            "12TR": "12. So. n. Trinitatis",
            "13TR": "13. So. n. Trinitatis",
            "14TR": "14. So. n. Trinitatis",
            "15TR": "15. So. n. Trinitatis",
            "16TR": "16. So. n. Trinitatis",
            "17TR": "17. So. n. Trinitatis",
            "18TR": "18. So n. Trinitatis",
            "19TR": "19. So. n. Trinitatis",
            "2ADV": "2. Advent",
            "2TRI": "2. So. n. Trinitatis",
            "2NCF": "2. So. nach Christfest",
            "2EPI": "2. So. nach Epiphanias",
            "20TR": "20. So. n. Trinitatis",
            "21TR": "21. So. n. Trinitatis",
            "22TR": "22. So. n. Trinitatis",
            "23TR": "23. So. n. Trinitatis",
            "3ADV": "3. Advent",
            "3TRI": "3. So. n. Trinitatis",
            "3EPI": "3. So. nach Epiphanias",
            "4ADV": "4. Advent",
            "4TRI": "4. So. n. Trinitatis",
            "4VPA": "4. So. v. d. Passionszeit",
            "5TRI": "5. So. n. Trinitatis",
            "5VPA": "5. So. v. d. Passionszeit",
            "6TRI": "6. So. n. Trinitatis",
            "7TRI": "7. So. n. Trinitatis",
            "8TRI": "8. So. n. Trinitatis",
            "9TRI": "9. So. n. Trinitatis",
            "ALTJ": "Altjahresabend",
            "ASCH": "Aschermittwoch",
            "BUBE": "Buß-und Bettag",
            "CHF1": "Christfest I",
            "CHF2": "Christfest II",
            "CHHF": "Christi Himmelfahrt",
            "CHRN": "Christnacht",
            "CHRV": "Christvesper",
            "DRTL": "Drittl.S.d.Kj.",
            "EPIP": "Epiphanias (Erscheinungsfest)",
            "ERNT": "Erntedank",
            "ESTO": "Estomihi",
            "EWIG": "Ewigkeitssonntag",
            "EXAU": "Exaudi",
            "PEPA": "Gedenktag der Apostel Petrus und Paulus",
            "AUGU": "Gedenktag der Augsburgischen Konfession",
            "ALLH": "Gedenktag der Heiligen",
            "GRDO": "Gründonnerstag",
            "INVO": "Invocavit",
            "ISRA": "Israelsonntag: Gedenktag der Zerstörung Jerusalemns",
            "JUBI": "Jubilate",
            "JUDI": "Judika",
            "KANT": "Kantate",
            "KARF": "Karfreitag",
            "KARA": "Karfreitag (Andacht zur Todesstunde Jesu)",
            "KARV": "Karfreitag (Vesper)",
            "KARS": "Karsamstag",
            "KONF": "Konfirmation",
            "LEPI": "Letzter So. nach Epiphanias",
            "LAET": "Lätare",
            "MART": "Martinstag (Bischof Martin von Tours)",
            "MISD": "Misericordias Domini",
            "NEUJ": "Neujahrstag",
            "NIKO": "Nikolaustag",
            "OKUL": "Okuli",
            "OSTM": "Ostermontag",
            "OSTN": "Osternacht",
            "OSTS": "Ostersonntag",
            "PALM": "Palmarum / Palmsonntag",
            "PFIM": "Pfingstmontag",
            "PFIS": "Pfingstsonntag",
            "QUAS": "Quasimodogeniti",
            "REFO": "Reformationsfest",
            "REMI": "Reminiszere",
            "ROGA": "Rogate",
            "SEPT": "Septuagesimä",
            "SEXA": "Sexagesimä",
            "MAVE": "Tag der Ankündigung der Geburt Jesu (Mariä Verkündigung)",
            "PHJA": "Tag der Apostel Philippus und Jakobus des Jüngeren",
            "SIJU": "Tag der Apostel Simon und Judas",
            "ARBE": "Tag der Arbeit (Bittag um gesegnete Arbeit)",
            "BEAP": "Tag der Berufung des Apostels Paulus",
            "BESJ": "Tag der Beschneidung und Namensgebung Jesu",
            "LIME": "Tag der Darstellung Jesu im Tempel (Lichtmess)",
            "JOHA": "Tag der Geburt Johannes des Täufers (Johannis)",
            "MAMA": "Tag der Maria Magdalena",
            "DEUT": "Tag der deutschen Einheit",
            "UNSK": "Tag der unschuldigen Kinder",
            "ANDR": "Tag des Apostels Andreas",
            "BART": "Tag des Apostels Bartholomäus",
            "JAKO": "Tag des Apostels Jakobus des Älteren",
            "MATI": "Tag des Apostels Matthias",
            "THOM": "Tag des Apostels Thomas",
            "THO2": "Tag des Apostels Thomas",
            "JOHN": "Tag des Apostels und Evangelisten Johannes",
            "MATA": "Tag des Apostels und Evangelisten Matthäus",
            "MAEL": "Tag des Besuchs Marias bei Elisabeth (Mariä Heimsuchung)",
            "MICH": "Tag des Erzengels Michael und aller Engel",
            "STEP": "Tag des Erzmärtyrers Stephanus",
            "LUKA": "Tag des Evangelisten Lukas",
            "MARK": "Tag des Evangelisten Markus",
            "POGR": "Tag des Gedenkens an die Novemberprogrome",
            "GONS": "Tag des Gedenkens an die Opfer des Nationalsozialismus",
            "TOTE": "Totensonntag (Gedenktag der Entschlafenen)",
            "TRIN": "Trinitatis",
            "VALE": "Valentinstag",
            "VORL": "Vorletzter Sonntag d. Kj."
        };

        let myItems = [];
        for (const codeKey in codes) {
            for (let i=1; i<=6; i++) {
                myItems.push({
                    id: codeKey+'-'+i,
                    title: codes[codeKey],
                    year: i,
                });
            }
        }

        return {
            myValue: this.value,
            myItems,
            settings: {
                valueField: 'id',
                labelField: 'title',
                searchField: ['title'],
                allowEmptyOption: true,
                showEmptyOptionInDropDown: true,
                render: {
                    item(item, escape) {
                        var t = '<div class="proprium-item">'
                        +' '+item.title+' <span class="text-muted">(Lesejahr '+item.year+')</span>'
                        t += '</div>';
                        return t;
                    },
                    option(item, escape) {
                        var t = '<div class="proprium-option">'
                        +' '+item.title+' <span class="text-muted">(Lesejahr '+item.year+')</span>'
                        t += '</div>';
                        return t;
                    }

                }
            },

        }
    }
}
</script>

<template>
    <form-selectize :label="label" :options="myItems" :settings="settings"
                    v-model="myValue" @input="myValue=$event; $emit('input', $event)" />
</template>

<style scoped>

>>> .proprium-option .text-muted, >>> .proprium-item .text-muted {
    font-size: .8em;
}

</style>
