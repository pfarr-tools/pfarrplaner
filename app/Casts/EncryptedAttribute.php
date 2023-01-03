<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EncryptedAttribute implements CastsAttributes
{
    static $encrypter = null;

    private static function parseKey($key)
    {
        if (Str::startsWith($key, 'base64:')) {
            return base64_decode(Str::after($key, 'base64:'));
        }
        return $key;
    }

    public static function getEncrypter()
    {
        if (!self::$encrypter) {
            self::$encrypter = new Encrypter(self::parseKey(config('database.key')), config('database.cipher'));
        }
        return self::$encrypter;
    }

    private function isEncrypted($string)
    {
        return isset(json_decode(base64_decode($string), true)['mac']);
    }

    private function hasObsoleteEncryption($string)
    {
        $prefix = chr(1) . chr(2) . '__LARAVEL-DATABASE-ENCRYPTED-';
        return substr($string, 0, strlen($prefix)) == $prefix && str_contains($string, chr(4));
    }

    private function decryptObsoleteEncryption($string)
    {
        $decrypted = Crypt::decryptString(substr($string, strpos($string, chr(4))));
        return @unserialize($decrypted) ?: $decrypted;
    }

    private function decrypt($string)
    {
        try {
            $decrypted = self::getEncrypter()->decryptString($string);
        } catch (DecryptException $e) {
            $decrypted = Crypt::decryptString($string);
        }

        return $decrypted;
    }

    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        if ($this->isEncrypted($value)) {
            return self::getEncrypter()->decryptString($value);
        }
        if ($this->hasObsoleteEncryption($value)) {
            return $this->decryptObsoleteEncryption($value);
        }
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param array $value
     * @param array $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return self::encrypt($value);
    }

    public static function encrypt($value) {
        if (!is_string($value) && !is_null($value)) $value = serialize($value);
        return self::getEncrypter()->encryptString($value);
    }
}
