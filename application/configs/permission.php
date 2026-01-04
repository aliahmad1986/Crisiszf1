<?php

$permission = array(
    'User' => array(
        'title' => 'مدیریت کاربران',
        'icon' => 'fa-user',
        'moduleName' => 'User',
        'controllerList' =>
            array(
                'Mnggroup' => array(
                    'controllersName' => 'Mnggroup',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده لیست گروهها',

                        ),
                        'addgroup' => array(
                            'actionName' => 'addgroup',
                            'title' => 'افزودن گروه جدید',

                        ),
                        'editgroup' => array(
                            'actionName' => 'editgroup',
                            'title' => 'ویرایش گروه',

                        ),
                        'deletegroup' => array(
                            'actionName' => 'deletegroup',
                            'title' => 'حذف گروه',

                        ),
                        'getgrouplist' => array(
                            'actionName' => 'getgrouplist',
                            'title' => 'جزئیات لیستی گروهها',

                        ),
                        'savegroup' => array(
                            'actionName' => 'savegroup',
                            'title' => 'ذخیره اطلاعات گروهها',

                        ),
                    )
                )
                ,
                'Mngperson' => array(
                    'controllersName' => 'Mngperson',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده لیست افراد',

                        ),
                        'addperson' => array(
                            'actionName' => 'addperson',
                            'title' => 'افزودن افراد جدید',

                        ),
                        'editperson' => array(
                            'actionName' => 'editperson',
                            'title' => 'ویرایش افراد',

                        ),
                        'deleteperson' => array(
                            'actionName' => 'deleteperson',
                            'title' => 'حذف افراد',

                        ),
                        'getpersonlist' => array(
                            'actionName' => 'getpersonlist',
                            'title' => 'جزئیات لیستی افراد',

                        ),
                        'saveperson' => array(
                            'actionName' => 'saveperson',
                            'title' => 'ذخیره اطلاعات افراد',

                        ),
                    )
                )
                ,
                'Mngusers' => array(
                    'controllersName' => 'Mngusers',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده لیست کاربران',

                        ),
                        'adduser' => array(
                            'actionName' => 'adduser',
                            'title' => 'افزودن کاربر جدید',

                        ),
                        'edituser' => array(
                            'actionName' => 'edituser',
                            'title' => 'ویرایش کاربر',

                        ),
                        'deleteuser' => array(
                            'actionName' => 'deleteuser',
                            'title' => 'حذف کاربر',

                        ),
                        'getuserlist' => array(
                            'actionName' => 'getuserlist',
                            'title' => 'جزئیات لیستی کاربران',

                        ),
                        'saveuser' => array(
                            'actionName' => 'saveuser',
                            'title' => 'ذخیره اطلاعات کاربران',

                        ),
                    )
                ),
                'Mngcustomer' => array(
                    'controllersName' => 'Mngcustomer',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده لیست مشتریان',

                        ),
                        'addcustomer' => array(
                            'actionName' => 'addcustomer',
                            'title' => 'افزودن مشتری جدید',

                        ),
                        'editcustomer' => array(
                            'actionName' => 'editcustomer',
                            'title' => 'ویرایش مشتری',

                        ),
                        'deletecustomer' => array(
                            'actionName' => 'deletecustomer',
                            'title' => 'حذف مشتری',

                        ),
                        'getcustomerlist' => array(
                            'actionName' => 'getcustomerlist',
                            'title' => 'جزئیات لیستی مشتریان',

                        ),
                        'savecustomer' => array(
                            'actionName' => 'savecustomer',
                            'title' => 'ذخیره اطلاعات مشتریان',

                        ),
                    )
                )
            )
    )
    ,
    
    'Setting' => array(
        'title' => 'تنظیمات سایت',
        'icon' => 'fa-user',
        'moduleName' => 'Setting',
        'controllerList' =>
            array(
                'Mngslider' => array(
                    'controllersName' => 'Mngslider',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده نمایشگر محصولات',

                        ),
                        'addslider' => array(
                            'actionName' => 'addslider',
                            'title' => 'افزودن نمایشگر جدید',

                        ),
                        'editslider' => array(
                            'actionName' => 'editslider',
                            'title' => 'ویرایش  نمایشگر',

                        ),
                        'deleteslider' => array(
                            'actionName' => 'deleteslider',
                            'title' => 'حذف  نمایشگر',

                        ),
                        'getsliderlist' => array(
                            'actionName' => 'getsliderlist',
                            'title' => 'جزئیات لیستی نمایشگر ',

                        ),
                        'saveslider' => array(
                            'actionName' => 'saveslider',
                            'title' => 'ذخیره نمایشگر',

                        ),
                    )
                ),
                'Mngcar' => array(
                    'controllersName' => 'Mngcar',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده گروه خودرو',

                        ),
                        'addcar' => array(
                            'actionName' => 'addcar',
                            'title' => 'افزودن  گروه خودرو جدید',

                        ),
                        'editcar' => array(
                            'actionName' => 'editcar',
                            'title' => 'ویرایش   گروه خودرو',

                        ),
                        'deletecar' => array(
                            'actionName' => 'deletecar',
                            'title' => 'حذف   گروه خودرو',

                        ),
                        'getcarlist' => array(
                            'actionName' => 'getcarlist',
                            'title' => 'جزئیات لیستی  گروه خودرو ',

                        ),
                        'savecar' => array(
                            'actionName' => 'savecar',
                            'title' => 'ذخیره  گروه خودرو',

                        ),
                    )
                ),
                'Mngcopon' => array(
                    'controllersName' => 'Mngcopon',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده کوپن',

                        ),
                        'addcopon' => array(
                            'actionName' => 'addcopon',
                            'title' => 'افزودن کوپن جدید',

                        ),
                        'editcopon' => array(
                            'actionName' => 'editcopon',
                            'title' => 'ویرایش  کوپن ',

                        ),
                        'deletecopon' => array(
                            'actionName' => 'deletecopon',
                            'title' => 'حذف  کوپن ',

                        ),
                        'getcoponlist' => array(
                            'actionName' => 'getcoponlist',
                            'title' => 'جزئیات لیستی کوپن  ',

                        ),
                        'savecopon' => array(
                            'actionName' => 'savecopon',
                            'title' => 'ذخیره کوپن ',

                        ),
                    )
                ),
                'Mngmagicbasket' => array(
                    'controllersName' => 'Mngmagicbasket',
                    'actions' => array(
                        'index' => array(
                            'actionName' => 'index',
                            'title' => 'مشاهده سبد شگفت انگیز',

                        ),
                        'addmagicbasket' => array(
                            'actionName' => 'addmagicbasket',
                            'title' => 'افزودن سبد شگفت انگیز جدید',

                        ),
                        'editmagicbasket' => array(
                            'actionName' => 'editmagicbasket',
                            'title' => 'ویرایش  سبد شگفت انگیز ',

                        ),
                        'deletemagicbasket' => array(
                            'actionName' => 'deletemagicbasket',
                            'title' => 'حذف  سبد شگفت انگیز ',

                        ),
                        'getmagicbasketlist' => array(
                            'actionName' => 'getmagicbasketlist',
                            'title' => 'جزئیات لیستی سبد شگفت انگیز  ',

                        ),
                        'savemagicbasket' => array(
                            'actionName' => 'savemagicbasket',
                            'title' => 'ذخیره سبد شگفت انگیز ',

                        ),
                    )
                )


            ),

    )

);