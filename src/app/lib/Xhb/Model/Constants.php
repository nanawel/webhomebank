<?php
namespace Xhb\Model;

/**
 * Class Constants
 *
 * Most constants names and values come from HomeBank source files.
 * @see src/enums.h
 *
 * @package Xhb\Model
 */
class Constants
{
    const ACC_TYPE_NONE       = 0;
    const ACC_TYPE_BANK       = 1;    //Banque
    const ACC_TYPE_CASH       = 2;    //Espèce
    const ACC_TYPE_ASSET      = 3;    //Actif (avoir)
    const ACC_TYPE_CREDITCARD = 4;    //Carte crédit
    const ACC_TYPE_LIABILITY  = 5;    //Passif (dettes)
    const ACC_TYPE_CHECKING	  = 6; 	  //OFX A standard checking account
    const ACC_TYPE_SAVINGS    = 7;    //OFX A standard savings account
    //    const ACC_TYPE_STOCK      = 6;    //Actions
    //    const ACC_TYPE_MUTUALFUND = 7;    //Fond de placement
    //    const ACC_TYPE_INCOME     = 8;    //Revenus
    //    const ACC_TYPE_EXPENSE    = 9;    //Dépenses
    //    const ACC_TYPE_EQUITY     = 10;    //Capitaux propres
    //    const ACC_TYPE_,

    public static $ACC_TYPE = array(
        'none'       => self::ACC_TYPE_NONE,
        'bank'       => self::ACC_TYPE_BANK,
        'cash'       => self::ACC_TYPE_CASH,
        'asset'      => self::ACC_TYPE_ASSET,
        'creditcard' => self::ACC_TYPE_CREDITCARD,
        'liability'  => self::ACC_TYPE_LIABILITY,
        'checking'   => self::ACC_TYPE_CHECKING,
        'savings'    => self::ACC_TYPE_SAVINGS,
    );

    const PAYMODE_NONE          = 0;
    const PAYMODE_CCARD         = 1;
    const PAYMODE_CHECK         = 2;
    const PAYMODE_CASH          = 3;
    const PAYMODE_XFER          = 4;
    const PAYMODE_INTXFER       = 5;
    /* 4.1 new payments here */
    const PAYMODE_DCARD         = 6;
    const PAYMODE_REPEATPMT     = 7;
    const PAYMODE_EPAYMENT      = 8;
    const PAYMODE_DEPOSIT       = 9;
    const PAYMODE_FEE           = 10;
    /* 4.6 new paymode */
    const PAYMODE_DIRECTDEBIT   = 11;
    //    PAYMODE_,
    const NUM_PAYMODE_MAX       = 12;

    public static $PAYMODES = array(
        'none'        => self::PAYMODE_NONE,
        'ccard'       => self::PAYMODE_CCARD,
        'check'       => self::PAYMODE_CHECK,
        'cash'        => self::PAYMODE_CASH,
        'xfer'        => self::PAYMODE_XFER,
        'intxfer'     => self::PAYMODE_INTXFER,
        'dcard'       => self::PAYMODE_DCARD,
        'repeatpmt'   => self::PAYMODE_REPEATPMT,
        'epayment'    => self::PAYMODE_EPAYMENT,
        'deposit'     => self::PAYMODE_DEPOSIT,
        'fee'         => self::PAYMODE_FEE,
        'directdebit' => self::PAYMODE_DIRECTDEBIT
    );

    const TXN_STATUS_NONE       = 0;
    const TXN_STATUS_CLEARED    = 1;
    const TXN_STATUS_RECONCILED = 2;
    const TXN_STATUS_REMIND     = 3;
    const TXN_STATUS_VOID       = 4;

    public static $TXN_STATUS = array(
        'none'       => self::TXN_STATUS_NONE,
        'cleared'    => self::TXN_STATUS_CLEARED,
        'reconciled' => self::TXN_STATUS_RECONCILED,
        'remind'     => self::TXN_STATUS_REMIND,
        'void'       => self::TXN_STATUS_VOID
    );

    const BALANCE_TYPE_TODAY        = 'today';
    const BALANCE_TYPE_BANK         = 'bank';
    const BALANCE_TYPE_FUTURE       = 'future';

    public static $BALANCE_TYPES = array(
        'today'    => self::BALANCE_TYPE_TODAY,
        'bank'     => self::BALANCE_TYPE_BANK,
        'future'   => self::BALANCE_TYPE_FUTURE
    );

    /*
     * @see src/gtk-chart-colors.c
     */
    public static $CHARTS_DEFAULT_COLORS = array(
        array(72, 118, 176),
        array(180, 198, 230),
        array(227, 126, 35),
        array(238, 186, 123),
        array(97, 158, 58),
        array(175, 222, 142),
        array(184, 43, 44),
        array(231, 151, 149),
        array(136, 103, 185),
        array(190, 174, 210),
        array(127, 87, 77),
        array(184, 155, 147),
        array(202, 118, 190),
        array(230, 181, 208),
        array(126, 126, 126),
        array(198, 198, 198),
        array(187, 188, 56),
        array(218, 218, 144),
        array(109, 189, 205),
        array(176, 217, 228),
        array(237, 212, 0),
        array(255, 239, 101),
        array(207, 93, 96),
        array(234, 186, 187),
        array(193, 124, 17),
        array(240, 181, 90),
        array(186, 189, 182),
        array(225, 227, 223),
        array(115, 210, 22),
        array(175, 240, 112),
        array(255, 140, 90),
        array(255, 191, 165),
    );
}
