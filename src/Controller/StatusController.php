<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

final class StatusController extends AbstractController
{
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
