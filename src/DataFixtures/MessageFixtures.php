<?php
// src/DataFixtures/MessageFixtures.php
namespace App\DataFixtures;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MessageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $messages = [
            // Conversation liée à la première réservation
            [
                'sender' => 'tenant-1',
                'receiver' => 'owner-1',
                'subject' => 'Question sur votre yourte',
                'content' => 'Bonjour, je suis intéressé par votre yourte. Est-elle adaptée aux enfants ?',
                'sentAt' => '-2 months -2 days',
                'isRead' => true,
                'propertyId' => 0,
                'bookingId' => null
            ],
            [
                'sender' => 'owner-1',
                'receiver' => 'tenant-1',
                'subject' => 'Re: Question sur votre yourte',
                'content' => 'Bonjour, oui la yourte est parfaitement adaptée aux enfants. Nous avons même un petit espace de jeux.',
                'sentAt' => '-2 months -1 days',
                'isRead' => true,
                'propertyId' => 0,
                'bookingId' => null
            ],
            // Conversation liée à la réservation future
            [
                'sender' => 'tenant-3',
                'receiver' => 'owner-1',
                'subject' => 'Précision sur l\'arrivée',
                'content' => 'Bonjour, je voulais savoir à quelle heure nous pourrons récupérer les clés ?',
                'sentAt' => '-3 days',
                'isRead' => true,
                'propertyId' => 0,
                'bookingId' => 2
            ],
            [
                'sender' => 'owner-1',
                'receiver' => 'tenant-3',
                'subject' => 'Re: Précision sur l\'arrivée',
                'content' => 'Bonjour, vous pouvez arriver à partir de 15h. Je vous attendrai sur place.',
                'sentAt' => '-2 days',
                'isRead' => false,
                'propertyId' => 0,
                'bookingId' => 2
            ]
        ];

        foreach ($messages as $messageData) {
            $message = new Message();
            $message->setSender($this->getReference($messageData['sender'], User::class));
            $message->setReceiver($this->getReference($messageData['receiver'], User::class));
            $message->setSubject($messageData['subject']);
            $message->setContent($messageData['content']);
            $message->setSentAt(new \DateTimeImmutable($messageData['sentAt']));
            $message->setIsRead($messageData['isRead']);
            $message->setPropertyId($messageData['propertyId']);
            $message->setBookingId($messageData['bookingId']);
            
            $manager->persist($message);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PropertyFixtures::class,
            BookingFixtures::class,
        ];
    }
}