<?php

namespace Diamante\FrontBundle\Controller;

use Diamante\DeskBundle\Model\Entity\Exception\EntityNotFoundException;
use Diamante\FrontBundle\Api\Command\ChangePasswordCommand;
use Diamante\FrontBundle\Api\Command\ConfirmCommand;
use Diamante\FrontBundle\Api\Command\RegisterCommand;
use Diamante\FrontBundle\Api\Command\ResetPasswordCommand;
use Diamante\FrontBundle\Api\Command\SendConfirmCommand;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;

/**
 * @RouteResource("User")
 * @NamePrefix("diamante_front_api_")
 */
class UserController extends FOSRestController
{
    /**
     * Register new Diamante User
     *
     * @Post("/user")
     * @ApiDoc(
     *      description="Register (create) new Diamante User",
     *      resource=true
     * )
     */
    public function registerAction()
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $command = new RegisterCommand();
        $command->email = $request->get('email');
        $command->password = $request->get('password');
        $command->firstName = $request->get('first_name');
        $command->lastName = $request->get('last_name');

        $errors = $this->get('validator')->validate($command);

        if (count($errors)) {
            return $this->response($this->view(null, Codes::HTTP_BAD_REQUEST));
        }

        try {
            $diamanteUSerId = $this->get('diamante.front.registration.service')->register($command);
            $view = $this->view(['success' => true, 'diamanteUserId' => $diamanteUSerId], Codes::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.diamante')->error(sprintf('Registration failed for user %s', $command->email));
            $view = $this->view(['message' => $e->getMessage()], Codes::HTTP_BAD_REQUEST);
        }
        return $this->response($view);
    }

    /**
     * Confirm new Diamante User registration
     *
     * @Patch("/user/confirm")
     * @ApiDoc(
     *      description="Confirm new Diamante User registration",
     *      resource=true
     * )
     */
    public function confirmAction()
    {
        $command = new ConfirmCommand();
        $command->hash = $this->getRequest()->get('hash');

        $errors = $this->get('validator')->validate($command);

        if (count($errors)) {
            return $this->response($this->view(null, Codes::HTTP_BAD_REQUEST));
        }

        try {
            $this->get('diamante.front.registration.service')->confirm($command);
            $view = $this->view(null, Codes::HTTP_OK);
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.diamante')->error(sprintf('Confirmation failed for hash %s', $command->hash));
            $view = $this->view(null, Codes::HTTP_BAD_REQUEST);
        }
        return $this->response($view);
    }

    /**
     * Reset user password action
     *
     * @Patch("/user/reset")
     * @ApiDoc(
     *      description="Reset user password",
     *      resource=true
     * )
     */
    public function resetAction()
    {
        $command = new ResetPasswordCommand();
        $command->email = $this->getRequest()->get('email');
        try {
            $resetService = $this->container->get('diamante.front.reset_password.service');
            $resetService->resetPassword($command);
            $view = $this->view(null, Codes::HTTP_OK);
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.diamante')->error(sprintf('Password reset failed for user %s', $command->email));
            $view = $this->view(['message' => $e->getMessage()], Codes::HTTP_NOT_FOUND);
        }
        return $this->response($view);
    }

    /**
     * Update user password action
     *
     * @Patch("/user/password")
     * @ApiDoc(
     *      description="Update user password",
     *      resource=true
     * )
     */
    public function passwordAction()
    {
        $command = new ChangePasswordCommand();
        $command->hash = $this->getRequest()->get('hash');
        $command->password = $this->getRequest()->get('password');
        try {
            $resetService = $this->container->get('diamante.front.reset_password.service');
            $resetService->changePassword($command);
            $view = $this->view(null, Codes::HTTP_OK);
        } catch (\Exception $e) {
            $view = $this->view(['message' => $e->getMessage()], Codes::HTTP_NOT_FOUND);
        }
        return $this->response($view);
    }

    /**
     * Send email for user confirmation
     *
     * @Patch("/user/sendConfirmation")
     * @ApiDoc(
     *      description="Send email for user confirmation",
     *      resource=true
     * )
     */
    public function sendConfirmAction()
    {
        $command = new SendConfirmCommand();
        $command->email = $this->getRequest()->get('email');

        try {
            $this->get('diamante.front.send_confirm.service')->send($command);
            $view = $this->view(null, Codes::HTTP_OK);
        } catch (EntityNotFoundException $e) {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $view = $this->view(null, Codes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->response($view);
    }

    /**
     * @param View $view
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function response(View $view)
    {
        return $this->get('fos_rest.view_handler')->handle($view);
    }
}
