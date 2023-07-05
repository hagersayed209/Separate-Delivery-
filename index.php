<?php
add_action("woocommerce_thankyou", "split_order_by_shipping_class");

function split_order_by_shipping_class($order_id)
{
    $order = wc_get_order($order_id);
    $groups = [];
    $new_order_ids = [];

    $shipping_method = $order->get_shipping_method();

    $customer_data = [
        "first_name" => $order->get_billing_first_name(),
        "last_name" => $order->get_billing_last_name(),
        "email" => $order->get_billing_email(),
        "phone" => $order->get_billing_phone(),
        "address_1" => $order->get_billing_address_1(),
        "address_2" => $order->get_billing_address_2(),
        "city" => $order->get_billing_city(),
        "state" => $order->get_billing_state(),
        "postcode" => $order->get_billing_postcode(),
        "country" => $order->get_billing_country(),
    ];

    if ($shipping_method == "Separate shipping") {
        foreach ($order->get_items() as $item) {
            $shipping_class = $item->get_product()->get_shipping_class();
            if (!isset($groups[$shipping_class])) {
                $groups[$shipping_class] = [];
            }
            $groups[$shipping_class][] = $item;
        }

        foreach ($groups as $shipping_class => $group) {
            $new_order = wc_create_order([
                "customer_id" => $order->get_customer_id(),
            ]);

            $new_order->set_billing_first_name($customer_data["first_name"]);
            $new_order->set_billing_last_name($customer_data["last_name"]);
            $new_order->set_billing_email($customer_data["email"]);
            $new_order->set_billing_phone($customer_data["phone"]);
            $new_order->set_billing_address_1($customer_data["address_1"]);
            $new_order->set_billing_address_2($customer_data["address_2"]);
            $new_order->set_billing_city($customer_data["city"]);
            $new_order->set_billing_state($customer_data["state"]);
            $new_order->set_billing_postcode($customer_data["postcode"]);
            $new_order->set_billing_country($customer_data["country"]);
            $new_order->set_shipping_first_name($customer_data["first_name"]);
            $new_order->set_shipping_last_name($customer_data["last_name"]);
            $new_order->set_shipping_address_1($customer_data["address_1"]);
            $new_order->set_shipping_address_2($customer_data["address_2"]);
            $new_order->set_shipping_city($customer_data["city"]);
            $new_order->set_shipping_state($customer_data["state"]);
            $new_order->set_shipping_postcode($customer_data["postcode"]);
            $new_order->set_shipping_country($customer_data["country"]);

            foreach ($group as $item) {
                $new_order->add_item(clone $item);
            }

            $shipping_cost = round(
                (float) ($order->get_shipping_total() / count($groups)),
                2
            );

            $new_order_shipping = new WC_Shipping_Rate(
                "",
                "cost",
                $shipping_cost,
                [],
                $shipping_class
            );
            $new_order->add_shipping($new_order_shipping);

            $new_order->calculate_totals();

            $new_order->update_status("processing");

            array_push($new_order_ids, $new_order->get_id());

            $new_order->save();
        }

        wp_delete_post($order_id, true);
    }
}
