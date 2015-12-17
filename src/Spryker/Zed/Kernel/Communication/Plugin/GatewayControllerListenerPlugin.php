<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Kernel\Communication\Plugin;

use Spryker\Shared\Transfer\TransferInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Shared\ZedRequest\Client\Message;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;
use Spryker\Zed\Application\Communication\Plugin\TransferObject\TransferServer;
use Spryker\Zed\Kernel\Communication\GatewayControllerListenerInterface;
use Spryker\Zed\Kernel\Communication\KernelCommunicationFactory;
use Spryker\Zed\ZedRequest\Business\Client\Request;
use Spryker\Zed\ZedRequest\Business\Client\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Spryker\Zed\Messenger\MessengerConfig;

/**
 * @method KernelCommunicationFactory getFactory()
 */
class GatewayControllerListenerPlugin extends AbstractPlugin implements GatewayControllerListenerInterface
{

    /**
     * @param FilterControllerEvent $event
     *
     * @return callable
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $currentController = $event->getController();
        $controller = $currentController[0];
        $action = $currentController[1];

        if (!($controller instanceof AbstractGatewayController)) {
            return $currentController;
        }

        $newController = function () use ($controller, $action) {

            MessengerConfig::setMessageTray(MessengerConfig::IN_MEMORY_TRAY);

            $requestTransfer = $this->getRequestTransfer($controller, $action);
            $result = $controller->$action($requestTransfer->getTransfer(), $requestTransfer);
            $response = $this->getResponse($controller, $result);

            return TransferServer::getInstance()
                ->setResponse($response)
                ->send();
        };

        $event->setController($newController);
    }

    /**
     * @param AbstractGatewayController $controller
     * @param string $action
     *
     * @return Request
     * @throw \LogicException
     */
    private function getRequestTransfer(AbstractGatewayController $controller, $action)
    {
        $classReflection = new \ReflectionObject($controller);
        $methodReflection = $classReflection->getMethod($action);
        $parameters = $methodReflection->getParameters();
        $countParameters = count($parameters);

        if ($countParameters > 2 || $countParameters === 2 && end($parameters)->getClass() !== 'Spryker\\Shared\\Library\\Transfer\\Request') {
            throw new \LogicException('Only one transfer object can be received in yves-action');
        }

        /** @var \ReflectionParameter $parameter */
        $parameter = array_shift($parameters);
        if ($parameter) {
            $class = $parameter->getClass();
            if (empty($class)) {
                throw new \LogicException('You need to specify a class for the parameter in the yves-action.');
            }

            $this->validateClassIsTransferObject($class);
        }

        return TransferServer::getInstance()->getRequest();
    }

    /**
     * @param AbstractGatewayController $controller
     * @param $result
     *
     * @return Response
     */
    protected function getResponse(AbstractGatewayController $controller, $result)
    {
        $response = new Response();

        if ($result instanceof TransferInterface) {
            $response->setTransfer($result);
        }

        $this->setGatewayControllerMessages($controller, $response);
        $this->setMessengerMessages($response);

        $response->setSuccess($controller->getSuccess());

        return $response;
    }

    /**
     * @param AbstractGatewayController $controller
     * @param Response $response
     *
     * @return void
     */
    protected function setGatewayControllerMessages(AbstractGatewayController $controller, Response $response)
    {
        $response->addSuccessMessages($controller->getSuccessMessages());
        $response->addInfoMessages($controller->getInfoMessages());
        $response->addErrorMessages($controller->getErrorMessages());
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    protected function setMessengerMessages(Response $response)
    {
        $MessengerFacade = $this->getFactory()->createMessengerFacade();

        $MessengerTransfer = $MessengerFacade->getStoredMessages();
        if ($MessengerTransfer === null) {
            return;
        }

        $response->addErrorMessages(
            $this->createResponseMessages(
                $MessengerTransfer->getErrorMessages(),
                $response->getErrorMessages()
            )
        );
        $response->addInfoMessages(
            $this->createResponseMessages(
                $MessengerTransfer->getInfoMessages(),
                $response->getInfoMessages()
            )
        );
        $response->addSuccessMessages(
            $this->createResponseMessages(
                $MessengerTransfer->getSuccessMessages(),
                $response->getSuccessMessages()
            )
        );
    }

    /**
     * @param \ArrayObject $messages
     * @param array|Message[] $storedMessages
     *
     * @return array|Message[]
     */
    protected function createResponseMessages(\ArrayObject $messages, array $storedMessages = [])
    {
        foreach ($messages as $message) {
            $responseMessage = new Message();
            $responseMessage->setMessage($message);
            $storedMessages[] = $responseMessage;
        }

        return $storedMessages;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return bool
     */
    protected function validateClassIsTransferObject(\ReflectionClass $class)
    {
        if (substr($class->getName(), 0, 16) === 'Generated\Shared') {
            return true;
        }

        if ($class->getName() === 'Spryker\Shared\Transfer\TransferInterface') {
            return true;
        }

        throw new \LogicException('Only transfer classes are allowed in yves action as parameter');
    }

}