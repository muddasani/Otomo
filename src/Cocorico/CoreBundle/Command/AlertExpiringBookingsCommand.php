<?php

namespace Cocorico\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * Bookings expiring alert
 * Every  15 minutes :
 */

//Cron: */15 * * * *  user   php app/console cocorico:bookings:alertExpiring

class AlertExpiringBookingsCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('cocorico:bookings:alertExpiring')
            ->setDescription('Alert Expiring Bookings.')
            ->addOption(
                'alert-delay',
                null,
                InputOption::VALUE_OPTIONAL,
                'Booking expiring alert delay in minutes. To use only on no prod env'
            )
            ->addOption(
                'expiration-delay',
                null,
                InputOption::VALUE_OPTIONAL,
                'Booking expiration delay in minutes. To use only on no prod env'
            )
            ->addOption(
                'acceptation-delay',
                null,
                InputOption::VALUE_OPTIONAL,
                'Booking acceptation delay in minutes. To use only on no prod env'
            )
            ->addOption(
                'test',
                null,
                InputOption::VALUE_NONE,
                'Extra precaution to ensure to use on test mode'
            )
            ->setHelp("Usage php app/console cocorico:bookings:alertExpiring");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $expirationDelay = $acceptationDelay = null;
        $alertDelay = $this->getContainer()->getParameter('cocorico.booking.alert_expiration_delay');

        if ($input->getOption('test') && $input->getOption('alert-delay') &&
            $input->getOption('expiration-delay') && $input->getOption('acceptation-delay')
        ) {
            $expirationDelay = $input->getOption('expiration-delay');
            $acceptationDelay = $input->getOption('acceptation-delay');
            $alertDelay = $input->getOption('alert-delay');
        }

        $result = $this->getContainer()->get('cocorico.booking.manager')
            ->alertExpiringBookings($alertDelay, $expirationDelay, $acceptationDelay);

        $output->writeln($result . " booking(s) expiring alerted");
    }

}
