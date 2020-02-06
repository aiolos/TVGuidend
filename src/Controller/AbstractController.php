<?php declare(strict_types=1);

namespace App\Controller;

use App\Tvheadend\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

abstract class AbstractController extends SymfonyAbstract
{
    protected $tvheadendClient;

    public function __construct(Client $client)
    {
        $this->tvheadendClient = $client;
    }

    protected function handleAction(Request $request)
    {
        if ($request->get('action', false) !== false) {
            switch (strtolower($request->get('action'))) {
                case 'delete':
                    $this->tvheadendClient->delete($request->get('uuid'));
                    break;

                case 'record':
                    $this->tvheadendClient->record($request->get('event'));
                    break;

                case 'recordseries':
                    $this->tvheadendClient->autorecord($request->get('event'));
                    break;

                case 'cancel':
                    $this->tvheadendClient->cancel($request->get('uuid'));
                    break;

                case 'cancelseries':
                    $this->tvheadendClient->cancelAutorec($request->get('uuid'));
                    break;
            }
        }
    }
}
