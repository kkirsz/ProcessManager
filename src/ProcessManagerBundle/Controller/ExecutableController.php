<?php
/**
 * Process Manager.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://github.com/dpfaffenbauer/ProcessManager/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace ProcessManagerBundle\Controller;

use CoreShop\Bundle\ResourceBundle\Controller\ResourceController;
use ProcessManagerBundle\Event\ExecutableEvents;
use ProcessManagerBundle\Form\Type\ExecutableFilterType;
use ProcessManagerBundle\Model\ExecutableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecutableController extends ResourceController
{
    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getConfigAction(Request $request)
    {
        $types = $this->getConfigTypes();

        return $this->viewHandler->handle([
            'types' => array_keys($types)
        ]);
    }

    /**
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     * @return mixed|JsonResponse
     */
    public function listAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $class = $this->repository->getClassName();
        $listingClass = $class.'\Listing';

        /**
         * @var Executable\Listing $list
         */
        $list = new $listingClass();
        $event = new GenericEvent($this, ['list' => $list]);
        $eventDispatcher->dispatch(ExecutableEvents::BEFORE_LIST_LOAD, $event);
        $list = $event->getArgument('list');

        $data = $list->getItems(
            $request->get('start', 0),
            $request->get('limit', 50)
        );

        return $this->viewHandler->handle($data, ['group' => 'List']);
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listByTypeAction(Request $request)
    {
        $type = $request->get('type');

        if ($type) {
            $result = $this->repository->findByType($type);

            return $this->viewHandler->handle(['data' => $result, 'success' => true], ['group' => 'List']);
        }

        return $this->viewHandler->handle(['success' => false, 'message' => 'No type given']);
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function runAction(Request $request)
    {
        $exe = $this->repository->find($request->get('id'));

        if (!$exe instanceof ExecutableInterface) {
            return $this->viewHandler->handle([
                'success' => false
            ]);
        }

        $params = [];
        $form = $this->get('process_manager.startup_resolver')->resolveFormType($exe);

        if ($form) {
            $form = $this->createForm($form);
            $startupConfig = $request->get('startupConfig', '{}');
            $startupConfig = json_decode($startupConfig, true);
            $handledForm = $form->submit($startupConfig);

            if (!$handledForm->isValid()) {
                $errors = $this->formErrorSerializer->serializeErrorFromHandledForm($handledForm);

                return $this->viewHandler->handle([
                    'success' => false,
                    'message' => 'Startup Parameters given are not valid'.PHP_EOL.PHP_EOL.implode(PHP_EOL, $errors),
                ]);
            }

            $params = $form->getData();
        }

        $this->get('process_manager.registry.processes')->get($exe->getType())->run($exe, $params);

        return $this->viewHandler->handle([
            'success' => true
        ]);
    }

    /**
     * @return array
     */
    protected function getConfigTypes()
    {
        return $this->getParameter('process_manager.processes');
    }
}
