<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
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

namespace App\Integrations\Youtube;

use App\Integrations\AbstractIntegration;
use App\Models\Places\City;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_LiveChatMessage;
use Google_Service_YouTube_LiveChatMessageListResponse;
use Google_Service_YouTube_LiveChatMessageSnippet;
use Google_Service_YouTube_LiveChatTextMessageDetails;

/**
 * Class YoutubeIntegration
 */
class YoutubeIntegration extends AbstractIntegration
{
    /** @var Google_Client */
    protected $client = null;

    /** @var Google_Service_YouTube */
    protected $youtube = null;

    /**
     * Determine whether the application has the credentials required for YouTube.
     */
    public static function isConfigured(): bool
    {
        return (bool) config('google.client_id') && (bool) config('google.client_secret');
    }

    /**
     * Create an authenticated YouTube client from the application configuration.
     */
    public function __construct()
    {
        $client = new Google_Client;
        $client->setAuthConfig(config('google'));
        $client->addScope(Google_Service_YouTube::YOUTUBE);
        $this->setClient($client);

        $youtube = new Google_Service_YouTube($client);
        $this->setYoutube($youtube);
    }

    /**
     * @return YoutubeIntegration
     */
    public static function get(City $city)
    {
        $instance = (new self);
        $instance->authenticate($city);

        return $instance;
    }

    /**
     * Authenticate the client with the refresh token stored for a city.
     *
     * @param  City  $city  City whose Google credentials should be used.
     */
    public function authenticate(City $city)
    {
        $this->client->setAccessToken($city->google_access_token);
        $token = $this->client->refreshToken($city->google_refresh_token);
        $this->client->setAccessToken($token);
    }

    /**
     * Returns true if youtube integration is active for a particular city.
     *
     * This is the case when a youtube channel is specified.
     */
    public static function isActive(City $city): bool
    {
        return $city->youtube_channel_url != '';
    }

    /**
     * @return mixed|null
     */
    public function getVideo($id)
    {
        return $this->youtube->videos->listVideos('snippet,contentDetails,statistics,status,liveStreamingDetails', ['id' => $id])->getItems()[0] ?? null;
    }

    /**
     * @param  array  $data
     * @return Google_Service_YouTube_LiveChatMessageListResponse
     */
    public function getLiveChat($id, $data = [])
    {
        return $this->getYoutube()->liveChatMessages->listLiveChatMessages($id, 'id,snippet,authorDetails', $data);
    }

    /**
     * Return the configured YouTube API service.
     */
    public function getYoutube(): Google_Service_YouTube
    {
        return $this->youtube;
    }

    /**
     * Replace the YouTube API service used by the integration.
     *
     * @param  Google_Service_YouTube  $youtube  API service instance.
     */
    public function setYoutube(Google_Service_YouTube $youtube): void
    {
        $this->youtube = $youtube;
    }

    /**
     * Send a text message to a YouTube live chat.
     *
     * @param  string  $liveChatId  YouTube live chat identifier.
     * @param  string  $author  Name displayed as the message author.
     * @param  string  $message  Message text supplied by the author.
     */
    public function sendLiveChatMessage($liveChatId, $author, $message)
    {
        $snippet = new Google_Service_YouTube_LiveChatMessageSnippet;
        $messageDetails = new Google_Service_YouTube_LiveChatTextMessageDetails;
        $messageDetails->setMessageText(
            $author.' hat über die Homepage folgende Nachricht eingegeben: '.PHP_EOL.PHP_EOL.$message
        );
        $snippet->setTextMessageDetails($messageDetails);
        $snippet->setLiveChatId($liveChatId);
        $snippet->setType('textMessageEvent');

        $liveChatInsert = new Google_Service_YouTube_LiveChatMessage;
        $liveChatInsert->setSnippet($snippet);

        $this->getYoutube()->liveChatMessages->insert('snippet', $liveChatInsert);
    }

    /**
     * Return the Google API client.
     */
    public function getClient(): Google_Client
    {
        return $this->client;
    }

    /**
     * Replace the Google API client used by the integration.
     *
     * @param  Google_Client  $client  Configured Google API client.
     */
    public function setClient(Google_Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Get all streams (ids) for a city
     *
     * @return array Streams
     */
    public function getAllStreams()
    {
        $streams = [];
        $allStreams = $this->getYoutube()->liveStreams->listLiveStreams('id,snippet', ['mine' => 'true', 'maxResults' => 50]);
        foreach ($allStreams as $stream) {
            /** @var \Google_Service_YouTube_LiveStream $stream */
            $streams[$stream->getId()] = $stream->getSnippet()->getTitle();
        }
        asort($streams);

        return $streams;
    }
}
