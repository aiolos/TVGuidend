<?php

namespace App\Tvheadend;

use Carbon\Carbon;
use Symfony\Component\HttpClient\HttpClient;

class Client
{
    /** @var \Symfony\Contracts\HttpClient\HttpClientInterface */
    private $client;

    public function getEpgNow(string $channelName): array
    {
        $playing = $this->getClient()->request(
            'GET',
            '/api/epg/events/grid',
            ['query' => ['channel' => $channelName, 'mode' => 'now']]
        );

        return $playing->toArray()['entries'];
    }
    public function getEpg(?string $channelName, Carbon $start, Carbon $end): array
    {
        $playing = $this->getClient()->request(
            'POST',
            '/api/epg/events/grid',
            [
                'body' => [
                    'channel' => $channelName,
                    'filter' => json_encode([
                        ['field' => 'stop', 'type' => 'numeric', 'value' => $start->timestamp, 'comparison' => 'gt'],
                        ['field' => 'start', 'type' => 'numeric', 'value' => $end->timestamp, 'comparison' => 'lt'],
                    ]),
                ]
            ]
        );

        return $playing->toArray()['entries'];
    }

    public function getChannels(): array
    {
        $channels = $this->getClient()->request(
            'GET',
            '/api/channel/grid',
            ['query' => ['limit' => 9999]]);

        $channels = $channels->toArray()['entries'];
        usort($channels, function($a, $b) {
            return $a['name'] <=> $b['name'];
        });

        return $channels;
    }

    public function getRecordings(): array
    {
        return $this->getClient()->request(
            'GET',
            '/api/dvr/entry/grid',
            ['query' => ['limit' => 99999, 'sort' => 'start', 'dir' => 'desc']]
        )->toArray()['entries'];
    }

    public function getTimers()
    {
        return $this->getClient()->request(
            'GET',
            '/api/dvr/entry/grid_upcoming',
            ['query' => ['sort' => 'start']]
        )->toArray()['entries'];
    }

    public function record($eventId)
    {
        return $this->getClient()->request(
            'GET',
            '/api/dvr/entry/create_by_event',
            ['query' => ['event_id' => $eventId, 'config_uuid' => $this->getDvrProfiles()[0]['uuid']]]
        );

        return true;
    }

    public function getDvrProfiles()
    {
        return $this->getClient()->request(
            'GET',
            '/api/dvr/config/grid')->toArray()['entries'];
    }

    public function getServerInfo(): array
    {
        return $this->getClient()->request('GET', '/api/serverinfo')->toArray();
    }

    public function getInputStatus(): array
    {
        return $this->getClient()->request('GET', '/api/status/inputs')->toArray()['entries'];
    }

    public function getUrl(): string
    {
        return 'http://' . $_ENV['TVHEADEND_IP'] . ':' . $_ENV['TVHEADEND_PORT'];
    }

    private function getClient(): \Symfony\Contracts\HttpClient\HttpClientInterface
    {
        if ($this->client === null) {
            $this->client = HttpClient::createForBaseUri(
                $this->getUrl(),
                ['auth_basic' => [$_ENV['TVHEADEND_USER'], $_ENV['TVHEADEND_PASSWORD']]]
            );
        }

        return $this->client;
    }
}
