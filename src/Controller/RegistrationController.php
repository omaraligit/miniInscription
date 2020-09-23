<?php
// src/Controller/RegistrationController.php
namespace App\Controller;

use App\Entity\Member;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class RegistrationController extends AbstractController
{

    // return the home page with the form to register users (solo + bulk users .svc files)
    public function index()
    {
        return $this->render('home/index.html.twig');
    }

    // save new users to database with constrains
    /**
     * - Si c’est un marocain, l’âge minimum doit être de 16 ans
     * - Si c’est un étranger, l’âge min est 18 ans
     * - A partir de la 11ème inscription, l’âge du nouveau inscrit doit être entre la moyenne
     *   des âges + 10% et la moyenne des âges - 10%.
     * - Si l’inscription est faite entre 12h et 21h, l’état est valide, si non l’état est en attente
     */
    public function registerNewMember(Request $request)
    {

        // validation

        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to the action: createProduct(EntityManagerInterface $entityManager)
        $entityManager = $this->getDoctrine()->getManager();
    
        // data integration to model
        $member = new Member();
        $member->setNom($request->query->get('nom'));
        $member->setPrenom($request->query->get('prenom'));
        $member->setDateInscription(new DateTime());
        $member->setDateDeNaissance(new DateTime());
        $member->setPays($request->query->get('pays'));
        $member->setEtat($request->query->get('etat'));
        $member->setSexe($request->query->get('sexe'));
        $member->setTelephone($request->query->get('telephone'));

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($member);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($member, 'json');
        return $this->json($member);

    }
}
