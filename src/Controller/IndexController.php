<?php declare(strict_types=1);

namespace App\Controller;

use App\Tvheadend\Client;
use Carbon\Carbon;
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
        if ($request->get('action', false) !== false) {
            switch ($request->get('action')) {
                case 'record':
                    $this->tvheadendClient->record($request->get('event'));
                    break;
            }
        }
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
        $now = Carbon::now();
        $offset = $now->timestamp % 1800;

        $start = $now->copy()->subSeconds($offset);
        $end = $start->copy()->addSeconds($timespan);

        foreach ($channels as $channel) {
            $channel['epg'] = $this->tvheadendClient->getEpg($channel['name'], $start, $end);
            $enrichedChannels[] = $channel;
        }

        return $this->render('index/timeline.html.twig', [
            'channels' => $enrichedChannels,
            'start' => $start->timestamp,
            'end' => $end->timestamp,
            'now' => $now->timestamp,
            'timespan' => $timespan,
        ]);
    }

    /**
     * @Route("/channel", name="channel")
     */
    public function channel(Request $request)
    {
        return $this->channelWithName($request->get('channel'), $request->get('date'));
    }

    /**
     * @Route("/channel/{channelName}/{date}", name="channelWithName")
     */
    public function channelWithName(string $channelName = null, string $date = null)
    {
        if ($date === null) {
            $start = Carbon::now();
            $end = Carbon::today()->endOfDay();
        } else {
            $start = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
        }
        $channels = $this->tvheadendClient->getChannels();
        $channel = [$channels[0]];
        if ($channelName !== null) {
            $channel = array_filter($channels, function ($channel) use ($channelName) {
                return $channel['name'] === $channelName;
            });
        }
        $channelToShow = array_shift($channel);

        return $this->render('index/channel.html.twig', [
            'channels' => $channels,
            'channel' => $channelToShow,
            'channelEpg' => $this->tvheadendClient->getEpg($channelToShow['name'], $start, $end),
            'prev' => $start->copy()->subDay()->format('Y-m-d'),
            'next' => $start->copy()->addDay()->format('Y-m-d'),
            'today' => $start->timestamp,
        ]);
    }

    /**
     * @Route("/recordings", name="recordings")
     */
    public function recordings(Request $request)
    {
        if ($request->get('action', false) !== false) {
            switch ($request->get('action')) {
                case 'delete':
                    $this->tvheadendClient->delete($request->get('uuid'));
                    break;
            }
        }

        $recordings = $this->tvheadendClient->getRecordings();
        $recordings = array_filter($recordings, function ($recording) {
            return $recording['sched_status'] !== 'scheduled'
                && (in_array($recording['status'], ['Completed OK', 'Running']));
        });
        return $this->render('index/recordings.html.twig', [
            'recordings' => $recordings,
            'url' => $this->tvheadendClient->getUrl(),
        ]);
    }

    /**
     * @Route("/timers", name="timers")
     */
    public function getTimers()
    {
        $timers = $this->tvheadendClient->getTimers();

        return $this->render('index/timers.html.twig', [
            'timers' => $timers,
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
