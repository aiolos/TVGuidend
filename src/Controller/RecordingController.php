<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RecordingController extends AbstractController
{
    /**
     * @Route("/recordings", name="recordings")
     */
    public function recordings(Request $request)
    {
        $this->handleAction($request);

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
    public function getTimers(Request $request)
    {
        $this->handleAction($request);

        $timers = $this->tvheadendClient->getTimers();

        return $this->render('index/timers.html.twig', [
            'timers' => $timers,
        ]);
    }
}
