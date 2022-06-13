<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RandomController extends AbstractController
{
    /**
     * @Route("/random/{name}/numbers/{length}", name="random")
     */
    function lucky_number($name, $length): Response {

        return $this->render('test.html.twig', [
            'name'=>ucfirst($name),
            'nums' => array_map(function(){return rand(0,100);}, range(0,$length)),
        ]);
    }
}