<?php declare(strict_types=1);

namespace App\Controller;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController extends AbstractController
{
    /**
     * @Route("/", name="now")
     */
    public function now(Request $request)
    {
        $this->handleAction($request);
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
     * @Route("/timeline/{start}/{timespan}", name="timeline")
     */
    public function timeline(Request $request, int $start = null, int $timespan = 14400)
    {
        $this->handleAction($request);
        $channels = $this->tvheadendClient->getChannels();
        $now = Carbon::now();
        if ($start == null) {
            $start = $now->copy();
        } else {
            $start = Carbon::createFromTimestamp($start);
        }

        $offset = $start->timestamp % 1800;

        $start = $start->subSeconds($offset);
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
            'url' => $this->tvheadendClient->getUrl(),
        ]);
    }

    /**
     * @Route("/channel", name="channel")
     */
    public function channel(Request $request)
    {
        return $this->channelWithName($request, $request->get('channel'), $request->get('date'));
    }

    /**
     * @Route("/channel/{channelName}/{date}", name="channelWithName")
     */
    public function channelWithName(Request $request, string $channelName = null, string $date = null)
    {
        $this->handleAction($request);

        $limit = 100;
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
            'channelEpg' => $this->tvheadendClient->getEpg($channelToShow['name'], $start, $end, $limit),
            'prev' => $start->copy()->subDay()->format('Y-m-d'),
            'next' => $start->copy()->addDay()->format('Y-m-d'),
            'today' => $start->timestamp,
            'url' => $this->tvheadendClient->getUrl(),
        ]);
    }
}
