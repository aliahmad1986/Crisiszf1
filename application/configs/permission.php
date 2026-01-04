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


);