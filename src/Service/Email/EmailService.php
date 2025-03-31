<?php

namespace App\Service\Email;

use App\Entity\Booking;
use App\Entity\Message;
use App\Entity\Property;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        string $senderEmail = 'contact@atypikhouse.com',
        string $senderName = 'AtypikHouse'
    ) {
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Send a welcome email to a new user
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Bienvenue sur AtypikHouse!')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send an email to verify user account
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Vérifiez votre compte AtypikHouse')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verification_url' => $verificationUrl
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a booking confirmation to a tenant
     */
    public function sendBookingConfirmationToTenant(Booking $booking): void
    {
        $tenant = $booking->getTenant();
        $property = $booking->getProperty();

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($tenant->getEmail(), $tenant->getFullName()))
            ->subject('Confirmation de votre réservation - ' . $property->getTitle())
            ->htmlTemplate('emails/booking_confirmation_tenant.html.twig')
            ->context([
                'booking' => $booking,
                'tenant' => $tenant,
                'property' => $property
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a booking notification to a property owner
     */
    public function sendBookingNotificationToOwner(Booking $booking): void
    {
        $owner = $booking->getProperty()->getOwner();
        $tenant = $booking->getTenant();
        $property = $booking->getProperty();

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($owner->getEmail(), $owner->getFullName()))
            ->subject('Nouvelle réservation pour ' . $property->getTitle())
            ->htmlTemplate('emails/booking_notification_owner.html.twig')
            ->context([
                'booking' => $booking,
                'tenant' => $tenant,
                'owner' => $owner,
                'property' => $property
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a booking cancellation notification
     */
    public function sendBookingCancellationNotification(Booking $booking, User $cancelledBy): void
    {
        $owner = $booking->getProperty()->getOwner();
        $tenant = $booking->getTenant();
        $property = $booking->getProperty();

        // Send to owner if cancelled by tenant or admin
        if ($cancelledBy !== $owner) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($owner->getEmail(), $owner->getFullName()))
                ->subject('Annulation de réservation - ' . $property->getTitle())
                ->htmlTemplate('emails/booking_cancellation_owner.html.twig')
                ->context([
                    'booking' => $booking,
                    'tenant' => $tenant,
                    'owner' => $owner,
                    'property' => $property,
                    'cancelled_by' => $cancelledBy
                ]);

            $this->mailer->send($email);
        }

        // Send to tenant if cancelled by owner or admin
        if ($cancelledBy !== $tenant) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($tenant->getEmail(), $tenant->getFullName()))
                ->subject('Annulation de votre réservation - ' . $property->getTitle())
                ->htmlTemplate('emails/booking_cancellation_tenant.html.twig')
                ->context([
                    'booking' => $booking,
                    'tenant' => $tenant,
                    'owner' => $owner,
                    'property' => $property,
                    'cancelled_by' => $cancelledBy
                ]);

            $this->mailer->send($email);
        }
    }

    /**
     * Send a reminder about upcoming booking
     */
    public function sendUpcomingBookingReminder(Booking $booking): void
    {
        $tenant = $booking->getTenant();
        $property = $booking->getProperty();

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($tenant->getEmail(), $tenant->getFullName()))
            ->subject('Rappel - Votre séjour approche')
            ->htmlTemplate('emails/booking_reminder.html.twig')
            ->context([
                'booking' => $booking,
                'tenant' => $tenant,
                'property' => $property
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification about a new message
     */
    public function sendNewMessageNotification(Message $message): void
    {
        $sender = $message->getSender();
        $receiver = $message->getReceiver();

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($receiver->getEmail(), $receiver->getFullName()))
            ->subject('Nouveau message de ' . $sender->getFullName())
            ->htmlTemplate('emails/new_message.html.twig')
            ->context([
                'message' => $message,
                'sender' => $sender,
                'receiver' => $receiver
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification about property approval
     */
    public function sendPropertyApprovalNotification(Property $property, bool $isApproved): void
    {
        $owner = $property->getOwner();

        $subject = $isApproved
            ? 'Votre propriété a été approuvée'
            : 'Mise à jour concernant votre propriété';

        $template = $isApproved
            ? 'emails/property_approved.html.twig'
            : 'emails/property_rejected.html.twig';

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($owner->getEmail(), $owner->getFullName()))
            ->subject($subject . ' - ' . $property->getTitle())
            ->htmlTemplate($template)
            ->context([
                'owner' => $owner,
                'property' => $property
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send review reminder after checkout
     */
    public function sendReviewReminder(Booking $booking): void
    {
        $tenant = $booking->getTenant();
        $property = $booking->getProperty();

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($tenant->getEmail(), $tenant->getFullName()))
            ->subject('Partagez votre expérience - ' . $property->getTitle())
            ->htmlTemplate('emails/review_reminder.html.twig')
            ->context([
                'booking' => $booking,
                'tenant' => $tenant,
                'property' => $property
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification to owners about property type changes
     */
    public function sendPropertyTypeChangeNotification(array $owners, string $message): void
    {
        foreach ($owners as $owner) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($owner->getEmail(), $owner->getFullName()))
                ->subject('Mise à jour importante concernant les types de propriétés')
                ->htmlTemplate('emails/property_type_change.html.twig')
                ->context([
                    'owner' => $owner,
                    'message' => $message
                ]);

            $this->mailer->send($email);
        }
    }
}