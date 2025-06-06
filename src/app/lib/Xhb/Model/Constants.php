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

    public const ACC_TYPE = [
        'none'       => self::ACC_TYPE_NONE,
        'bank'       => self::ACC_TYPE_BANK,
        'cash'       => self::ACC_TYPE_CASH,
        'asset'      => self::ACC_TYPE_ASSET,
        'creditcard' => self::ACC_TYPE_CREDITCARD,
        'liability'  => self::ACC_TYPE_LIABILITY,
        'checking'   => self::ACC_TYPE_CHECKING,
        'savings'    => self::ACC_TYPE_SAVINGS,
    ];

    public const ACC_FLAG_CLOSED = (1<<1);
    public const ACC_FLAG_NOSUMMARY = (1<<4);
    public const ACC_FLAG_NOBUDGET = (1<<5);
    public const ACC_FLAG_NOREPORT = (1<<6);
    public const ACC_FLAG_OUTFLOWSUM = (1<<7);

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

    public const PAYMODES = [
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
    ];

    const TXN_STATUS_NONE       = 0;
    const TXN_STATUS_CLEARED    = 1;
    const TXN_STATUS_RECONCILED = 2;
    const TXN_STATUS_REMIND     = 3;
    const TXN_STATUS_VOID       = 4;

    public const TXN_STATUS = [
        'none'       => self::TXN_STATUS_NONE,
        'cleared'    => self::TXN_STATUS_CLEARED,
        'reconciled' => self::TXN_STATUS_RECONCILED,
        'remind'     => self::TXN_STATUS_REMIND,
        'void'       => self::TXN_STATUS_VOID
    ];

    const BALANCE_TYPE_TODAY        = 'today';
    const BALANCE_TYPE_BANK         = 'bank';
    const BALANCE_TYPE_FUTURE       = 'future';

    public const BALANCE_TYPES = [
        'today'    => self::BALANCE_TYPE_TODAY,
        'bank'     => self::BALANCE_TYPE_BANK,
        'future'   => self::BALANCE_TYPE_FUTURE
    ];

    /*
     * @see src/gtk-chart-colors.c
     */
    public static $CHARTS_DEFAULT_COLORS = [
        [72, 118, 176],
        [180, 198, 230],
        [227, 126, 35],
        [238, 186, 123],
        [97, 158, 58],
        [175, 222, 142],
        [184, 43, 44],
        [231, 151, 149],
        [136, 103, 185],
        [190, 174, 210],
        [127, 87, 77],
        [184, 155, 147],
        [202, 118, 190],
        [230, 181, 208],
        [126, 126, 126],
        [198, 198, 198],
        [187, 188, 56],
        [218, 218, 144],
        [109, 189, 205],
        [176, 217, 228],
        [237, 212, 0],
        [255, 239, 101],
        [207, 93, 96],
        [234, 186, 187],
        [193, 124, 17],
        [240, 181, 90],
        [186, 189, 182],
        [225, 227, 223],
        [115, 210, 22],
        [175, 240, 112],
        [255, 140, 90],
        [255, 191, 165],
    ];
}
