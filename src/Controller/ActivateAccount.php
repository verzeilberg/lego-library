<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Exception\AppException;
use App\Exception\NotFoundException;
use App\Service\UserService;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\VarDumper\VarDumper;

class ActivateAccount extends AbstractController
{
    /**
     * @throws NotFoundException
     */
    #[Route('/activate-account')]
    public function activateAccount(RequestStack $request, UserService $userService): Response
    {
        $title = $this->getParameter('app.title');
        $message = 'Account activated!';
        $token = $request->getCurrentRequest()->query->get('token');
        try {
            $userService->activate($token);
        } catch (NotFoundException $e) {
            $message = 'Gebruiker niet gevonden';
        } catch (JWTDecodeFailureException $e) {
            $message = 'Token is niet geldig';
        } catch (AppException|Exception $e) {
            $message = 'Er is een fout opgetreden';
        }

        return $this->render('account/activate.html.twig', [
            'message' => $message,
            'title' => $title,
        ]);
    }
}
