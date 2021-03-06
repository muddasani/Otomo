<?php

namespace Cocorico\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * Validate bookings commands
 * For example every two hours :
 */

//Cron: 0 */2  * * *  user   php app/console cocorico:bookings:checkBankWires

class CheckBookingsBankWiresCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('cocorico:bookings:checkBankWires')
            ->setDescription('Check Bookings Bank Wires. Set status to done if bank wire has been transferred')
            ->setHelp("Usage php app/console cocorico:bookings:checkBankWires");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->getContainer()->get('cocorico.booking_bank_wire.manager')->checkBookingsBankWires();
        $output->writeln($result . " booking(s) bank wires checked");
    }

}
