<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    /**
     * @Route("/", name="now")
     */
    public function now(Request $request)
    {
        $channels = $this->getChannels();

        $enrichedChannels = [];
        foreach ($channels as $channel) {
            $channel['now'] = $this->getEpgNow($channel['name'])[0];
            $channel['now']['percentage'] = intval(100 * (time() - $channel['now']['start'])/($channel['now']['stop'] - $channel['now']['start']));
            $enrichedChannels[] = $channel;
        }

        return $this->render('index/now.html.twig', [
            'channels' => $enrichedChannels,
            'url' => $this->getUrl(),
        ]);
    }

    /**
     * @Route("/timeline", name="timeline")
     */
    public function timeline()
    {
        $channels = $this->getChannels();
        $timespan = 4 * 3600;
        $now = time();
        $offset = $now % 1800;
        $start = $now - $offset;
        $end = $start + $timespan;

        foreach ($channels as $channel) {
            $channel['epg'] = $this->getEpg($channel['name'], $start, $end);
            $enrichedChannels[] = $channel;
        }

        return $this->render('index/timeline.html.twig', [
            'channels' => $enrichedChannels,
            'start' => $start,
            'end' => $end,
            'timespan' => $timespan,
        ]);
    }

    private function getChannels()
    {
        $client = HttpClient::create();
        $channels = $client->request('GET', $this->getUrl() . '/api/channel/grid?limit=9999');

        $channels = $channels->toArray()['entries'];
        usort($channels, function($a, $b) {
            return $a['name'] <=> $b['name'];
        });
        return $channels;
    }

    private function getUrl()
    {
        return 'http://' . $_ENV['TVHEADEND_USER'] . ':' . $_ENV['TVHEADEND_PASSWORD']
            . '@' . $_ENV['TVHEADEND_IP'] . ':' . $_ENV['TVHEADEND_PORT'];
    }

    private function getEpgNow(string $channelName): array
    {
        $client = HttpClient::create();

        $prog = urlencode($channelName);

        $playing = $client->request('GET', $this->getUrl() . '/api/epg/events/grid?channel=' . $prog . '&mode=now');
        return $playing->toArray()['entries'];

    }

    private function getEpg(string $channelName, int $start, int $end): array
    {
        $client = HttpClient::create();

        $playing = $client->request(
            'POST',
            $this->getUrl() . '/api/epg/events/grid',
            [
                'body' => [
                    'channel' => $channelName,
                    'filter' => json_encode([
                        ['field' => 'stop', 'type' => 'numeric', 'value' => $start, 'comparison' => 'gt'],
                        ['field' => 'start', 'type' => 'numeric', 'value' => $end, 'comparison' => 'lt'],
                    ]),
                ]
            ]
        );

        return $playing->toArray()['entries'];
    }
}