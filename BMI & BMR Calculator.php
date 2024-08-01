<?php
/*
Plugin Name: BMI & BMR Calculator
Description: A WordPress plugin to calculate BMI and BMR in the admin dashboard and store results in the database.
Version: 1.4
Author: Behrad Mahdavi
*/

// Create database table on plugin activation
function bmi_bmr_calculator_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bmi_bmr_calculator';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        bmi float NOT NULL,
        bmr float NOT NULL,
        calculated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'bmi_bmr_calculator_install');

// Add menu item to the dashboard
function bmi_bmr_calculator_menu() {
    add_menu_page(
        'BMI & BMR Calculator', 
        'BMI & BMR Calculator', 
        'manage_options', 
        'bmi-bmr-calculator', 
        'bmi_bmr_calculator_page', 
        'dashicons-calculator', 
        90 
    );
}
add_action('admin_menu', 'bmi_bmr_calculator_menu');

// Display the content of the calculator page
function bmi_bmr_calculator_page() {
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $table_name = $wpdb->prefix . 'bmi_bmr_calculator';
    
    if (isset($_POST['calculate_bmi']) && isset($_POST['calculate_bmr'])) {
        $weight = floatval($_POST['bmi_weight']);
        $height = floatval($_POST['bmi_height']) / 100;
        $bmi = $weight / ($height * $height);

        $weight_bmr = floatval($_POST['bmr_weight']);
        $height_bmr = floatval($_POST['bmr_height']);
        $age = intval($_POST['bmr_age']);
        $gender = $_POST['bmr_gender'];

        if ($gender === 'male') {
            $bmr = 88.362 + (13.397 * $weight_bmr) + (4.799 * $height_bmr) - (5.677 * $age);
        } else {
            $bmr = 447.593 + (9.247 * $weight_bmr) + (3.098 * $height_bmr) - (4.330 * $age);
        }

        // Save the results in the database
        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'bmi' => $bmi,
                'bmr' => $bmr,
                'calculated_at' => current_time('mysql'),
            ]
        );
        
        echo "<p>Your BMI is: " . number_format($bmi, 2) . "</p>";
        echo "<p>Your BMR is: " . number_format($bmr, 2) . " kcal/day</p>";
    }

    // Fetch previous results from the database
    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = $user_id ORDER BY calculated_at DESC");
    
    if ($results) {
        echo "<h2>Your Calculation History</h2>";
        echo "<table class='widefat fixed'>";
        echo "<thead><tr><th>Date</th><th>BMI</th><th>BMR</th></tr></thead>";
        echo "<tbody>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . esc_html($row->calculated_at) . "</td>";
            echo "<td>" . esc_html(number_format($row->bmi, 2)) . "</td>";
            echo "<td>" . esc_html(number_format($row->bmr, 2)) . " kcal/day</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    
    // Display the form
    ?>
    <div class="wrap">
        <h1>BMI & BMR Calculator</h1>
        <form method="post" action="">
            <h2>BMI Calculator</h2>
            <p>
                <label for="bmi_weight">Weight (kg):</label>
                <input type="number" id="bmi_weight" name="bmi_weight" required>
            </p>
            <p>
                <label for="bmi_height">Height (cm):</label>
                <input type="number" id="bmi_height" name="bmi_height" required>
            </p>

            <h2>BMR Calculator</h2>
            <p>
                <label for="bmr_weight">Weight (kg):</label>
                <input type="number" id="bmr_weight" name="bmr_weight" required>
            </p>
            <p>
                <label for="bmr_height">Height (cm):</label>
                <input type="number" id="bmr_height" name="bmr_height" required>
            </p>
            <p>
                <label for="bmr_age">Age:</label>
                <input type="number" id="bmr_age" name="bmr_age" required>
            </p>
            <p>
                <label for="bmr_gender">Gender:</label>
                <select id="bmr_gender" name="bmr_gender">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </p>
            <p>
                <input type="submit" name="calculate_bmi" value="Calculate BMI & BMR">
            </p>
        </form>
    </div>
    <?php
}
