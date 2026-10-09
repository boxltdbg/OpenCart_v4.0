<?php
// Heading
$_['heading_title']        = 'Модул за доставка на BOX NOW';
$_['heading_title_report'] = 'Справка за доставки чрез BOX NOW';
$_['heading_help']         = 'За да използвате Модула за доставки на BOX NOW е нужно да се регистрирате <a target="_blank" href="https://boxnow.bg/e-shops">тук</a>, след което трябва да попълните полетата, с която получите - във формата по-долу.';

// Text
$_['text_home']      = 'Начало';
$_['text_extension']  = 'Разширения';
$_['text_success']   = 'Вие успешно редактирахте Модула за доставка на BOX NOW!';
$_['text_edit']      = 'Редактирайте Модул за доставка на BOX NOW';

$_['tab_settings'] = 'Основни настройки';
$_['tab_pricing']  = 'Ценообразуване';

// Entry - settings
$_['entry_api_url']             = 'API URL';
$_['entry_client_id']           = 'Client ID';
$_['entry_client_secret']       = 'Client Secret';
$_['entry_warehouse_number']    = 'Номер на склад(овете)';
$_['entry_partner_id']          = 'Номер на партньора';
$_['entry_partner_name']        = 'Име на подател';
$_['entry_partner_email']       = 'Имейл на подател';
$_['entry_partner_phone']       = 'Телефон на подател';
$_['entry_partner_phone_hint']  = '+359';
$_['entry_geo_zone']            = 'Географска зона';
$_['entry_status']              = 'Статус';
$_['entry_sort_order']          = 'Сортирай поръчката';

// Entry - pricing
$_['entry_banned_weight']      = 'Забрани за тегло над:';
$_['entry_unit_of_measure']    = 'Мерна единица';
$_['entry_kilogram']           = 'Килограм';
$_['entry_gram']                = 'Грам';
$_['entry_cart_weight']         = 'Въведете тегло ако няма такова в количката';
$_['entry_free_shipping']      = 'Сума над която доставката е безплатна';
$_['entry_tax_class']          = 'Данъчен клас';

$_['entry_categories']           = 'Избери Категории';
$_['entry_categories_available']  = 'Позволени категории';
$_['entry_categories_selected']  = 'Категории за скриване на метода';
$_['entry_categories_search']    = 'Търси...';
$_['help_categories']            = 'Избери категории, при наличие на продукт от тях в количката, методът за доставка BOX NOW ще се скрие.';

$_['entry_payment_modules'] = 'Използва се при определени методи за плащане';
$_['help_payment_modules']  = 'Някои методи за плащане, като <b>Наложен Платеж</b>, е възможно да не могат да бъдат комбинирани с Модула за доставки на BOX NOW';

$_['entry_weight_from']  = 'Килограми - От';
$_['entry_weight_to']    = 'Килограми - До (включително)';
$_['entry_fixed_price']  = 'Твърда цена';

$_['button_save']              = 'Запис';
$_['button_back']               = 'Назад';
$_['button_remove']            = 'Премахни';
$_['button_weight_value_add']  = 'Добави тегловен диапазон';
$_['button_view']              = 'Преглед';

$_['text_all_zones'] = 'Всички зони';
$_['text_enabled']   = 'Разрешено';
$_['text_disabled']  = 'Забранено';
$_['text_none']      = ' --- Няма --- ';
$_['text_missing']   = 'Липсва';
$_['text_no_results'] = 'Няма намерени BOX NOW поръчки!';
$_['text_pagination'] = 'Показани от %d до %d от %d (%d Страници)';

// Report
$_['column_order_id']        = 'Поръчка №';
$_['column_customer']        = 'Клиент';
$_['column_order_status']    = 'Статус на поръчката';
$_['column_total']           = 'Сума';
$_['column_total_products']  = 'Общ брой';
$_['column_date_added']      = 'Дата';
$_['column_vouchers']        = 'Товарителници';
$_['column_info']            = 'Информация / Изтегли товарителница';
$_['column_locker_id']       = 'ID на автомат';
$_['column_box_now_status']  = 'Статус';

$_['text_voucher_success']        = 'Създаден';
$_['text_voucher_pending']        = 'В изчакване';
$_['text_voucher_cancelled']      = 'Отказана';
$_['text_voucher_notfound']       = 'Не е намерен';
$_['text_voucher_send']           = 'Създай товарителница';
$_['text_voucher_cancel']         = 'Откажи товарителница';
$_['text_voucher_cancel_confirm'] = 'Сигурни ли сте, че искате да откажете тази товарителница?';
$_['text_voucher_status_success'] = 'Успешно беше създадена товарителница';
$_['text_voucher_cancel_success'] = 'Товарителницата беше успешно отказана';
$_['text_voucher_cancel_error']   = 'Грешка при отказване на товарителницата';

$_['status_message_error'] = 'Не беше генерирана товарителница (Код на грешката: %s). %s';

$_['entry_customer_telephone']  = 'Телефон на клиента';
$_['entry_select_warehouse']    = 'Warehouse ID';
$_['entry_compartment_size']    = 'Размер на клетката';
$_['entry_compartment_small']   = 'Малък (60x45x8 см)';
$_['entry_compartment_medium']  = 'Среден (60x45x17 см)';
$_['entry_compartment_large']   = 'Голям (60x45x36 см)';

// Error
$_['error_order_locker']    = 'За поръчка №%s няма избран автомат. Изберете автомат от картата или въведете ID на автомата и опитайте отново.';
$_['error_order_telephone'] ='Поръчка №%s няма валиден телефон на клиента. Въведете го в полето „Телефон на клиента“ на реда и опитайте отново.';
$_['error_permission'] ='Нямате необходимите права, за да редактирате Mодула за доставки на BOX NOW!';
