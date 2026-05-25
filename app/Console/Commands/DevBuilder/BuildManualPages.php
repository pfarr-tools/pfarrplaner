<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands\DevBuilder;

use App\Services\PackageService;
use Illuminate\Console\Command;
class BuildManualPages extends Command
{
    protected const MANUAL_ROOT = 'manual';
    protected const USER_MANUAL_DIR = 'manual/benutzerhandbuch';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build:manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the online manual';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Writing generated front matter...');
        $this->writeGeneratedFrontMatter();
        $this->writeLicensePage();

        $this->line('Moving image files...');
        foreach (glob(base_path('manual/img*.png')) as $file) {
            copy($file, base_path(self::MANUAL_ROOT.'/media/images/'.basename($file)));
            unlink($file);
        }
        $this->line('Rewriting image references...');
        foreach ($this->getAllPages() as $file) {
            file_put_contents($file, str_replace('(img', '(media/images/img', file_get_contents($file)));
        }

        $this->line('Done');
    }

    protected function writeGeneratedFrontMatter()
    {
        $package = PackageService::info();
        $manualDateTime = now('Europe/Berlin')->isoFormat('DD.MM.YYYY HH:mm');
        $gitCommit = trim((string) shell_exec('git rev-parse --short HEAD 2>/dev/null')) ?: 'unbekannt';
        $gitBranch = trim((string) shell_exec('git branch --show-current 2>/dev/null')) ?: 'unbekannt';

        file_put_contents(base_path(self::USER_MANUAL_DIR.'/versionsangaben.md'), implode(PHP_EOL, [
            '[//]: # (TOC: 16. Versionsangaben)',
            '',
            '# Versionsangaben',
            '',
            '| Angabe | Wert |',
            '|---|---|',
            '| Handbuch | Pfarrplaner Benutzerhandbuch |',
            '| Programmversion | '.$package['info']['version'].' |',
            '| Umgebung | '.$package['env'].' |',
            '| Build-Datum der Anwendung | '.$package['buildDateString'].' |',
            '| Handbuch erstellt am | '.$manualDateTime.' |',
            '| Git-Branch | '.$gitBranch.' |',
            '| Git-Stand | '.$gitCommit.' |',
            '| Lizenz | GNU General Public License, Version 3.0 oder später |',
            '| Projekt | Pfarrplaner |',
            '| Autor und Copyright | Christoph Fischer, https://christoph-fischer.org |',
            '',
            '## Was ist neu?',
            '',
            'Die wichtigsten Änderungen der letzten Versionen stehen im Änderungsprotokoll. Für die tägliche Arbeit sind vor allem neue oder geänderte Schaltflächen, neue Berichte, neue Eingabefelder und geänderte Abläufe wichtig.',
            '',
            $this->normalizeGermanManualText($this->getRecentChanges()),
            '',
            'Pfarrplaner ist freie Software. Sie dürfen das Programm unter den Bedingungen der GNU General Public License Version 3 oder später weitergeben und verändern.',
            '',
            'Dieses Handbuch beschreibt die Bedienung für Benutzerinnen und Benutzer. Installation, Betrieb und technische Wartung sind nicht Teil dieses Handbuchs.',
            '',
        ]));
    }

    /**
     * Write the license chapter with project license text and dependency licenses.
     *
     * @return void
     */
    protected function writeLicensePage(): void
    {
        $licenseFile = base_path(self::MANUAL_ROOT.'/media/licenses/gpl-3.0.de.txt');
        $licenseText = file_exists($licenseFile) ? trim(file_get_contents($licenseFile)) : '';

        file_put_contents(base_path(self::USER_MANUAL_DIR.'/lizenzen.md'), implode(PHP_EOL, [
            '[//]: # (TOC: 17. Lizenzen)',
            '',
            '# Lizenzen',
            '',
            'Pfarrplaner ist freie Software. Sie dürfen Pfarrplaner weitergeben und verändern, wenn Sie die Bedingungen der GNU General Public License Version 3 oder später einhalten.',
            '',
            'Dieses Kapitel nennt zuerst die Lizenz von Pfarrplaner selbst. Danach folgt eine Übersicht der verwendeten Programmpakete und ihrer Lizenzen.',
            '',
            '## Lizenz von Pfarrplaner',
            '',
            'Pfarrplaner steht unter der GNU General Public License, Version 3 oder später.',
            '',
            'Der folgende Text ist eine inoffizielle deutsche Übersetzung der GNU General Public License, Version 3. Rechtlich verbindlich ist der englische Originaltext.',
            '',
            '```text',
            $licenseText,
            '```',
            '',
            '## Verwendete Programmpakete',
            '',
            $this->getDependencyLicenseMarkdown(),
            '',
        ]));
    }

    /**
     * @return string Markdown table with dependency license data
     */
    protected function getDependencyLicenseMarkdown(): string
    {
        $rows = [];

        $composerFile = base_path('.composer-licenses');
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            foreach (($composer['dependencies'] ?? []) as $package => $info) {
                $rows[] = [
                    'Composer',
                    $package,
                    $info['version'] ?? '',
                    implode(', ', $info['license'] ?? []),
                ];
            }
        }

        $npmFile = base_path('.npm-licenses');
        if (file_exists($npmFile)) {
            $npm = json_decode(file_get_contents($npmFile), true);
            foreach (($npm ?? []) as $package => $info) {
                $rows[] = [
                    'npm',
                    $package,
                    '',
                    is_array($info['licenses'] ?? null) ? implode(', ', $info['licenses']) : ($info['licenses'] ?? ''),
                ];
            }
        }

        if (!count($rows)) {
            return 'Die Paketliste konnte nicht automatisch aus den Lizenzdateien gelesen werden. Erzeugen Sie sie mit `npm run licenses:update` neu.';
        }

        usort($rows, fn($left, $right) => [$left[0], $left[1]] <=> [$right[0], $right[1]]);

        $markdown = [
            '| Bereich | Paket | Version | Lizenz |',
            '|---|---|---|---|',
        ];

        foreach ($rows as $row) {
            $markdown[] = '| '.implode(' | ', array_map(fn($value) => str_replace('|', '\\|', $value), $row)).' |';
        }

        return implode(PHP_EOL, $markdown);
    }

    /**
     * @return string Markdown excerpt with recent changelog entries
     */
    protected function getRecentChanges(): string
    {
        $file = base_path('CHANGELOG.md');
        if (!file_exists($file)) {
            return 'Für diese Version liegt kein Änderungsprotokoll vor.';
        }

        $lines = preg_split('/\R/', file_get_contents($file));
        $output = [];
        $versionCount = 0;

        foreach ($lines as $line) {
            if (preg_match('/^##\s+/', $line)) {
                $versionCount++;
                if ($versionCount > 3) {
                    break;
                }
            }

            if ($versionCount > 0) {
                $output[] = $line;
            }
        }

        return trim(implode(PHP_EOL, $output)) ?: 'Für diese Version liegen keine zusammengefassten Änderungen vor.';
    }

    protected function normalizeGermanManualText(string $text): string
    {
        return str_replace(
            [
                'Ae',
                'Oe',
                'Ue',
                'ae',
                'oe',
                'ue',
            ],
            [
                'Ä',
                'Ö',
                'Ü',
                'ä',
                'ö',
                'ü',
            ],
            $text
        );
    }

    protected function getTOC() {
        $toc = [];
        foreach ($this->getAllPages() as $file) {
            $page = file_get_contents($file);
            preg_match_all('/\[\/\/\]: \# \(TOC: (.*?)\)/', $page, $matches);
            if (count($matches[1])) {
                foreach ($matches[1] as $match) {
                    preg_match('/^((?:\d+\.)*)\s*(.*)$/', trim($match), $matches2);
                    if (count($matches2)) {
                        $toc[] = [
                            'number' => trim($matches2[1], '.'),
                            'title' => trim($matches2[2]),
                            'file' => basename($file),
                        ];
                    }
                }
            }
        }
        usort($toc, function ($a, $b) {
            return version_compare($a['number'], $b['number']);
        });
        return $toc;
    }

    protected function outputTOCLevel($data, $level = 0, $prefix='')
    {
        $o = '';
        foreach ($data as $item) {
            $label = str_starts_with($item['number'], '0')
                ? $item['title']
                : $item['number'].'. '.$item['title'];
            $o .= '* ['.$label.']('.$item['file'].')'.PHP_EOL;
        }
        return $o;
    }

    protected function getAllPages()
    {
        return glob(base_path(self::USER_MANUAL_DIR.'/*.md'));
    }

}
