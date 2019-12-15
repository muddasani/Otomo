<?php

namespace Cocorico\CoreBundle\Model\Manager;

use Cocorico\CoreBundle\Entity\Booking;
use Cocorico\CoreBundle\Entity\BookingBankWire;
use Cocorico\CoreBundle\Entity\BookingPayingRefund;
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Mailer\TwigSwiftMailer;
use Cocorico\CoreBundle\Repository\BookingPayingRefundRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class BookingPayingRefundManager extends BaseManager
{
    protected $em;
    protected $cancellationPolicyRules;
    protected $mailer;
    public $maxPerPage;

    /**
     * @param EntityManager   $em
     * @param array           $cancellationPolicyRules
     * @param TwigSwiftMailer $mailer
     * @param int             $maxPerPage
     */
    public function __construct(
        EntityManager $em,
        array $cancellationPolicyRules,
        TwigSwiftMailer $mailer,
        $maxPerPage
    ) {
        $this->em = $em;
        $this->cancellationPolicyRules = $cancellationPolicyRules;
        $this->mailer = $mailer;
        $this->maxPerPage = $maxPerPage;
    }

    /**
     * @param int   $askerId
     * @param int   $page
     * @param array $status
     *
     * @return Paginator
     *
     */
    public function findByAsker($askerId, $page, $status = array())
    {
        $queryBuilder = $this->getRepository()->getFindByAskerQuery($askerId, $status);

        //Pagination
        $queryBuilder
            ->setFirstResult(($page - 1) * $this->maxPerPage)
            ->setMaxResults($this->maxPerPage);

        //Query
        $query = $queryBuilder->getQuery();

        return new Paginator($query);
    }


    /**
     * @param int   $id
     * @param int   $askerId
     * @param array $status
     *
     * @return BookingBankWire|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByAsker($id, $askerId, $status = array())
    {
        $queryBuilder = $this->getRepository()->getFindOneByAskerQuery($id, $askerId, $status);

        $query = $queryBuilder->getQuery();

        return $query->getOneOrNullResult();
    }


    /**
     *  Get Amount to refund to asker when he cancel his booking
     *
     * @param Booking $booking
     * @return array
     *      "refund_amount" => amount to refund to asker
     *      "refund_percent" => amount percentage to refund to asker
     *      "fee_to_collect_while_refund" => fees to collect while refunding asker
     *
     * @throws \Exception
     */
    public function getFeeAndAmountToRefundToAsker(Booking $booking)
    {
        if ($booking->getStatus() == Booking::STATUS_PAYED) {
            if ($booking->getCancellationPolicy() == Listing::CANCELLATION_POLICY_FLEXIBLE) {
                $rules = $this->cancellationPolicyRules["flexible"];
            } elseif ($booking->getCancellationPolicy() == Listing::CANCELLATION_POLICY_STRICT) {
                $rules = $this->cancellationPolicyRules["strict"];
            } else {
                throw new \Exception("Invalid booking cancellation policy");
            }

            //If time before checkin is less than the limit then the refund is minimum
            if ($booking->getTimeBeforeStart() < $rules["time_before_start"]) {
                $refundPercentage = $rules["refund_min"];
            } else {
                $refundPercentage = $rules["refund_max"];
            }

            //Amount refunded is equal to total amount without fees
            $amountToRefund = $booking->getAmountExcludingFees() * $refundPercentage;

            $feeToCollectWhileRefund = 0;
            //If refund to asker is 100% then offerer fees are also refunded to asker
            //And asker fees are collected while refunding
            if ($refundPercentage == 1) {
                $amountToRefund += $booking->getAmountFeeAsOfferer();
                $feeToCollectWhileRefund = $booking->getAmountTotalFee() - $booking->getAmountFeeAsOfferer();
            }

            return array(
                "refund_amount" => round($amountToRefund),//We are in cents: Remove decimal if any
                "refund_percent" => $refundPercentage,
                "fee_to_collect_while_refund" => $feeToCollectWhileRefund
            );
        } else {
            throw new \Exception("Invalid booking status");
        }

    }


    /**
     * @param Booking $booking
     * @return array
     */
    public function getFeeAndAmountDecimalToRefundToAsker(Booking $booking)
    {
        $feeAndAmountToRefund = $this->getFeeAndAmountToRefundToAsker($booking);
        $feeAndAmountToRefund["refund_amount"] = $feeAndAmountToRefund["refund_amount"] / 100;
        $feeAndAmountToRefund["fee_to_collect_while_refund"] = $feeAndAmountToRefund["fee_to_collect_while_refund"] / 100;

        return $feeAndAmountToRefund;
    }


    /**
     * Get amount to refund or refunded to asker depending on booking status
     *
     * @param Booking $booking
     * @return array|int
     */
    public function getAmountDecimalToRefundOrRefundedToAsker(Booking $booking)
    {
        $amountTotal = 0;
        if ($booking->getStatus() == Booking::STATUS_PAYED) {//Not already canceled
            $amountTotal = $this->getFeeAndAmountDecimalToRefundToAsker($booking);
            $amountTotal = $amountTotal["refund_amount"];
        } elseif ($booking->getStatus() == Booking::STATUS_CANCELED_ASKER) {//Already canceled
            if ($payingRefund = $booking->getPayingRefund()) {
                $amountTotal = $payingRefund->getAmountDecimal();
            } else {
                $amountTotal = 0;//Canceled while not payed
            }
        } elseif ($booking->getStatus() == Booking::STATUS_NEW) {//Not already canceled no refunded
            $amountTotal = 0;//nothing to refund because amount not already payed
        }

        return $amountTotal;
    }


    /**
     * @param  BookingPayingRefund $bookingPayingRefund
     *
     * @return BookingPayingRefund
     */
    public function save(BookingPayingRefund $bookingPayingRefund)
    {
        $this->persistAndFlush($bookingPayingRefund);

        return $bookingPayingRefund;
    }


    /**
     * @return TwigSwiftMailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     *
     * @return BookingPayingRefundRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(BookingPayingRefund::class);
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
