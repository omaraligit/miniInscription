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
        $data = json_decode($request->getContent(), true);

        // age du member
        $age = DateTime::createFromFormat('Y-m-d',$data["date_de_naissance"] )->diff(new DateTime('now'))->y;
        // get totqle saved lins to db and test if over 11
        $countMmebersInDB =$this->getDoctrine()->getManager()
        ->getRepository(Member::class)
        ->createQueryBuilder('a')
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
            

        if ($countMmebersInDB >= 10) {
            // this case wee have more than 11 entries to db
            $avgOfAges =$this->getDoctrine()->getManager()
            ->getRepository(Member::class)
            ->createQueryBuilder('a')
                ->select('AVG(DATE_DIFF(CURRENT_DATE(),a.date_de_naissance)/356) AS age')
                ->getQuery()
                ->getSingleScalarResult();
            if(($avgOfAges * 0.9) > $age && $age > ($avgOfAges * 1.1))
                return $this->json(["error"=>"age : " . $age . "nest pas dans les limit des 10% " . ($avgOfAges * 0.9) . " - " . ($avgOfAges * 1.1) ],400);
        }else{
            if ($data["pays"] == "M") {
                // si marocain l'age doit etre 16 ou plus
                if($age < 16)
                    return $this->json(["error"=>"age non acceptable"],400);

            } else if ($data["pays"] == "E") {
                // si etranger l'age doit etre 18 ou plus
                if($age < 18)
                    return $this->json(["error"=>"age non acceptable"],400);
            }            
        }


        // si time entre 12 - 21 etat = V (valide) si non etat = NV (non valide)
        $etat = ($this->TimeIsBetweenTwoTimes(date("H:i:s"))) ? "V" : "NV" ;


        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to the action: createProduct(EntityManagerInterface $entityManager)
        $entityManager = $this->getDoctrine()->getManager();
        // data integration to model
        $member = new Member();
        $member->setNom($data["nom"]);
        $member->setPrenom($data["prenom"]);
        $member->setDateInscription(DateTime::createFromFormat('Y-m-d',date('Y-m-d') ));
        $member->setDateDeNaissance(DateTime::createFromFormat('Y-m-d',$data["date_de_naissance"] ));
        $member->setPays($data["pays"]);
        $member->setEtat($etat);
        $member->setSexe($data["sexe"]);
        $member->setTelephone($data["telephone"]);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($member);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->json($member,200);
    }



    function TimeIsBetweenTwoTimes($input) {
        $from = "12:00:00";
        $till = "21:00:00";
        $f = DateTime::createFromFormat('H:i:s', $from);
        $t = DateTime::createFromFormat('H:i:s', $till);
        $i = DateTime::createFromFormat('H:i:s', $input);
        if ($f > $t) $t->modify('+1 day');
        return ($f <= $i && $i <= $t) || ($f <= $i->modify('+1 day') && $i <= $t);
    }
}
