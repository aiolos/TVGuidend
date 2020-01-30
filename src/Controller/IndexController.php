<?php declare(strict_types=1);

namespace App\Controller;

use App\Tvheadend\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    private $tvheadendClient;

    public function __construct(Client $client)
    {
        $this->tvheadendClient = $client;
    }

    /**
     * @Route("/", name="now")
     */
    public function now(Request $request)
    {
        $channels = $this->tvheadendClient->getChannels();

        $enrichedChannels = [];
        foreach ($channels as $channel) {
            $channel['now'] = $this->tvheadendClient->getEpgNow($channel['name'])[0];
            $channel['now']['percentage'] = intval(100 * (time() - $channel['now']['start'])/($channel['now']['stop'] - $channel['now']['start']));
            $enrichedChannels[] = $channel;
        }

        return $this->render('index/now.html.twig', [
            'channels' => $enrichedChannels,
            'url' => $this->tvheadendClient->getUrl(),
        ]);
    }

    /**
     * @Route("/timeline", name="timeline")
     */
    public function timeline()
    {
        $channels = $this->tvheadendClient->getChannels();
        $timespan = 4 * 3600;
        $now = time();
        $offset = $now % 1800;
        $start = $now - $offset;
        $end = $start + $timespan;

        foreach ($channels as $channel) {
            $channel['epg'] = $this->tvheadendClient->getEpg($channel['name'], $start, $end);
            $enrichedChannels[] = $channel;
        }

        return $this->render('index/timeline.html.twig', [
            'channels' => $enrichedChannels,
            'start' => $start,
            'end' => $end,
            'now' => $now,
            'timespan' => $timespan,
        ]);
    }

    /**
     * @Route("/status", name="status")
     */
    public function status()
    {
        return $this->render('index/status.html.twig', [
            'serverInfo' => $this->tvheadendClient->getServerInfo(),
            'inputStatus' => $this->tvheadendClient->getInputStatus(),
        ]);
    }
}