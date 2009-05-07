Introduction
============

Extension for PunBB forums to allow dice rolls expressions for users.


Requeriments
===

PunBB version > 1.3.3 (needs to have the hook 'po_modify_quote_info')

Install
=======

Go to your PunBB directory:

    cd extensions
    git clone git://github.com/raistlin/pun_dice.git

Log in as admin in PunBB, go to Extensions, install Extension.


Usage
=====

    [dice]3d5[/dice] -> Rolled (3d5) : 2 + 4 + 1 = 7
    [dice]3d6+3[/dice] -> Rolled (3d5) : 1 + 3 + 4 + 3 = 11
    [dice]3d6+3>10[/dice] -> Rolled (3d5) : 1 + 3 + 4 + 3 > 10 : SUCCESS
    [dice]3d6+3<10[/dice] -> Rolled (3d5) : 1 + 3 + 4 + 3 < 10 : FAIL


Thanks to
=========
    Cebollinos from http://www.rolgratis.com/gyg/foros
    Meroka     from http://www.rolgratis.com/gyg/foros
    PunBB Development team
    All users of this extension

