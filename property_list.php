<?php
session_start();
require "includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
$city_name = $_GET["city"];

// Read filter values from GET
$min_rent = isset($_GET['min_rent']) && $_GET['min_rent'] !== '' ? intval($_GET['min_rent']) : null;
$max_rent = isset($_GET['max_rent']) && $_GET['max_rent'] !== '' ? intval($_GET['max_rent']) : null;
$gender = isset($_GET['gender']) && $_GET['gender'] !== '' ? $_GET['gender'] : null;
$sharing = isset($_GET['sharing']) ? $_GET['sharing'] : [];
$amenities = isset($_GET['amenities']) ? $_GET['amenities'] : [];

$sql_1 = "SELECT * FROM cities WHERE name = '$city_name'";
$result_1 = mysqli_query($conn, $sql_1);
if (!$result_1) {
    echo "Something went wrong!";
    return;
}
$city = mysqli_fetch_assoc($result_1);
if (!$city) {
    echo "Sorry! We do not have any PG listed in this city.";
    return;
}
$city_id = $city['id'];

// Build the base SQL for properties
$sql_2 = "SELECT p.* FROM properties p WHERE p.city_id = $city_id";

// Add rent filter
if ($min_rent !== null) {
    $sql_2 .= " AND p.rent >= $min_rent";
}
if ($max_rent !== null) {
    $sql_2 .= " AND p.rent <= $max_rent";
}
// Add gender filter
if ($gender !== null && in_array($gender, ['male', 'female', 'unisex'])) {
    $sql_2 .= " AND p.gender = '" . mysqli_real_escape_string($conn, $gender) . "'";
}
// Remove sharing type filter logic
// if (!empty($sharing)) {
//     $sharing_escaped = array_map(function($s) use ($conn) { return "'" . mysqli_real_escape_string($conn, $s) . "'"; }, $sharing);
//     $sql_2 .= " AND p.sharing_type IN (" . implode(",", $sharing_escaped) . ")";
// }
// Add amenities filter (assuming properties_amenities join)
if (!empty($amenities)) {
    $amenities_escaped = array_map(function($a) use ($conn) { return "'" . mysqli_real_escape_string($conn, $a) . "'"; }, $amenities);
    $amenities_count = count($amenities);
    $sql_2 .= " AND p.id IN (SELECT pa.property_id FROM properties_amenities pa INNER JOIN amenities a ON pa.amenity_id = a.id WHERE a.icon IN (" . implode(",", $amenities_escaped) . ") GROUP BY pa.property_id HAVING COUNT(DISTINCT a.icon) = $amenities_count)";
}

$result_2 = mysqli_query($conn, $sql_2);
if (!$result_2) {
    echo "Something went wrong!";
    return;
}
$properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);


$sql_3 = "SELECT * 
            FROM interested_users_properties iup
            INNER JOIN properties p ON iup.property_id = p.id
            WHERE p.city_id = $city_id";
$result_3 = mysqli_query($conn, $sql_3);
if (!$result_3) {
    echo "Something went wrong!";
    return;
}
$interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Best PG's in <?php echo $city_name ?> | PG Life</title>

    <?php
    include "includes/head_links.php";
    ?>
    <link href="css/property_list.css" rel="stylesheet" />
</head>

<body>
    <?php
    include "includes/header.php";
    ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2">
            <li class="breadcrumb-item">
                <a href="index.php">Home</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo $city_name; ?>
            </li>
        </ol>
    </nav>

    <div class="page-container">
        <div class="row">
            <!-- Sidebar Filters Start -->
            <div class="col-md-3">
                <form id="filter-form" method="GET" action="property_list.php">
                    <input type="hidden" name="city" value="<?php echo htmlspecialchars($city_name); ?>">
                    <div class="card mb-3">
                        <div class="card-header">Rent Range</div>
                        <div class="card-body">
                            <input type="number" class="form-control mb-2" name="min_rent" placeholder="Min Rent">
                            <input type="number" class="form-control" name="max_rent" placeholder="Max Rent">
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header">Gender</div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_any" value="" checked>
                                <label class="form-check-label" for="gender_any">Any</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male">
                                <label class="form-check-label" for="gender_male">Male</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female">
                                <label class="form-check-label" for="gender_female">Female</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_unisex" value="unisex">
                                <label class="form-check-label" for="gender_unisex">Unisex</label>
                            </div>
                        </div>
                    </div>
                    <!-- Sharing Type filter removed -->
                    <div class="card mb-3">
                        <div class="card-header">Amenities</div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="amenity_wifi">
                                <label class="form-check-label" for="amenity_wifi">WiFi</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="ac" id="amenity_ac">
                                <label class="form-check-label" for="amenity_ac">AC</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="food" id="amenity_food">
                                <label class="form-check-label" for="amenity_food">Food</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="laundry" id="amenity_laundry">
                                <label class="form-check-label" for="amenity_laundry">Laundry</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="parking" id="amenity_parking">
                                <label class="form-check-label" for="amenity_parking">Parking</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                </form>
            </div>
            <!-- Sidebar Filters End -->
            <div class="col-md-9">
                <div class="filter-bar row justify-content-around">
                    <div class="col-auto" data-toggle="modal" data-target="#filter-modal">
                        <img src="img/filter.png" alt="filter" />
                        <span>Filter</span>
                    </div>
                    <div class="col-auto">
                        <img src="img/desc.png" alt="sort-desc" />
                        <span>Highest rent first</span>
                    </div>
                    <div class="col-auto">
                        <img src="img/asc.png" alt="sort-asc" />
                        <span>Lowest rent first</span>
                    </div>
                </div>

                <?php
                foreach ($properties as $property) {
                    $property_images = glob("img/properties/" . $property['id'] . "/*");
                ?>
                    <div class="property-card row">
                        <div class="image-container col-md-4">
                            <img src="<?= $property_images[0] ?>" />
                        </div>
                        <div class="content-container col-md-8">
                            <div class="row no-gutters justify-content-between">
                                <?php
                                $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                                $total_rating = round($total_rating, 1);
                                ?>
                                <div class="star-container" title="<?= $total_rating ?>">
                                    <?php
                                    $rating = $total_rating;
                                    for ($i = 0; $i < 5; $i++) {
                                        if ($rating >= $i + 0.8) {
                                    ?>
                                            <i class="fas fa-star"></i>
                                        <?php
                                        } elseif ($rating >= $i + 0.3) {
                                        ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php
                                        } else {
                                        ?>
                                            <i class="far fa-star"></i>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="interested-container">
                                    <?php
                                    $interested_users_count = 0;
                                    $is_interested = false;
                                    foreach ($interested_users_properties as $interested_user_property) {
                                        if ($interested_user_property['property_id'] == $property['id']) {
                                            $interested_users_count++;

                                            if ($interested_user_property['user_id'] == $user_id) {
                                                $is_interested = true;
                                            }
                                        }
                                    }

                                    // Wishlist/Favorite button
                                    ?>
                                    <button class="btn btn-link p-0 favorite-btn" data-property-id="<?= $property['id'] ?>" title="Add to Favorites">
                                        <?php if ($is_interested) { ?>
                                            <i class="fas fa-heart text-danger"></i>
                                        <?php } else { ?>
                                            <i class="far fa-heart"></i>
                                        <?php } ?>
                                    </button>
                                    <div class="interested-text"><?= $interested_users_count ?> interested</div>
                                </div>
                            </div>
                            <div class="detail-container">
                                <div class="property-name"><?= $property['name'] ?></div>
                                <div class="property-address"><?= $property['address'] ?></div>
                                <div class="property-gender">
                                    <?php
                                    if ($property['gender'] == "male") {
                                    ?>
                                        <img src="img/male.png" />
                                    <?php
                                    } elseif ($property['gender'] == "female") {
                                    ?>
                                        <img src="img/female.png" />
                                    <?php
                                    } else {
                                    ?>
                                        <img src="img/unisex.png" />
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="row no-gutters">
                                <div class="rent-container col-6">
                                    <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                                    <div class="rent-unit">per month</div>
                                </div>
                                <div class="button-container col-6">
                                    <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">View</a>
                                    <button class="btn btn-secondary view-reviews-btn" data-property-id="<?= $property['id'] ?>" data-toggle="modal" data-target="#reviews-modal">View Reviews</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }

                if (count($properties) == 0) {
                ?>
                    <div class="no-property-container">
                        <p>No PG to list</p>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="filter-modal" tabindex="-1" role="dialog" aria-labelledby="filter-heading" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="filter-heading">Filters</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <h5>Gender</h5>
                    <hr />
                    <div>
                        <button class="btn btn-outline-dark btn-active">
                            No Filter
                        </button>
                        <button class="btn btn-outline-dark">
                            <i class="fas fa-venus-mars"></i>Unisex
                        </button>
                        <button class="btn btn-outline-dark">
                            <i class="fas fa-mars"></i>Male
                        </button>
                        <button class="btn btn-outline-dark">
                            <i class="fas fa-venus"></i>Female
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button data-dismiss="modal" class="btn btn-success">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Modal -->
    <div class="modal fade" id="reviews-modal" tabindex="-1" role="dialog" aria-labelledby="reviewsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewsModalLabel">PG Reviews</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="reviews-modal-body">
                    <!-- Reviews will be loaded here by JS -->
                </div>
            </div>
        </div>
    </div>

    <?php
    include "includes/signup_modal.php";
    include "includes/login_modal.php";
    include "includes/footer.php";
    ?>
    <script>
    // Hardcoded reviews data (should match includes/reviews_data.php)
    const pgReviews = {
        1: [
            {name: "Amit Sharma", rating: 5, text: "Great place to stay! Clean rooms and friendly staff."},
            {name: "Priya Singh", rating: 4, text: "Good amenities and safe environment."},
            {name: "Rahul Verma", rating: 2, text: "Rooms are small and sometimes noisy."},
            {name: "Sneha Patel", rating: 5, text: "Loved the food and the location is perfect."},
            {name: "Vikram Rao", rating: 3, text: "Average experience, but value for money."},
            {name: "Neha Gupta", rating: 1, text: "Had issues with water supply. Not recommended."},
            {name: "Rohan Mehta", rating: 4, text: "Nice PG, would stay again."},
            {name: "Kavita Joshi", rating: 5, text: "Excellent service and very clean."},
            {name: "Suresh Kumar", rating: 3, text: "Decent place, but could improve on maintenance."},
            {name: "Pooja Desai", rating: 5, text: "Highly recommended for students!"}
        ],
        2: [
            {name: "Manish Agarwal", rating: 4, text: "Comfortable stay and good food."},
            {name: "Divya Nair", rating: 5, text: "Loved the ambiance and staff support."},
            {name: "Sanjay Rao", rating: 2, text: "Rooms were not as clean as expected."},
            {name: "Ritika Jain", rating: 5, text: "Best PG in the area!"},
            {name: "Arjun Kapoor", rating: 3, text: "Okay for short stays."},
            {name: "Meera Sinha", rating: 1, text: "Very noisy and poor management."},
            {name: "Nikhil Das", rating: 4, text: "Good facilities and location."},
            {name: "Shweta Tripathi", rating: 5, text: "Superb experience!"},
            {name: "Ramesh Babu", rating: 3, text: "Average, but affordable."},
            {name: "Anjali Menon", rating: 5, text: "Would recommend to friends."}
        ],
        3: [
            {name: "Sunil Kumar", rating: 5, text: "Amazing PG, very clean and well maintained."},
            {name: "Ritu Sharma", rating: 4, text: "Good food and friendly staff."},
            {name: "Deepak Singh", rating: 2, text: "Too far from main road."},
            {name: "Ankita Mehra", rating: 5, text: "Loved my stay here!"},
            {name: "Vikas Jain", rating: 3, text: "Average, but affordable."},
            {name: "Megha Kapoor", rating: 1, text: "Had issues with cleanliness."},
            {name: "Ramesh Yadav", rating: 4, text: "Nice rooms and good security."},
            {name: "Pallavi Joshi", rating: 5, text: "Highly recommended!"},
            {name: "Sanjana Rao", rating: 3, text: "Decent, but food can improve."},
            {name: "Aakash Gupta", rating: 5, text: "Best PG experience so far."}
        ],
        4: [
            {name: "Kiran Desai", rating: 4, text: "Comfortable and safe."},
            {name: "Nitin Verma", rating: 5, text: "Excellent staff and amenities."},
            {name: "Priyanka Sinha", rating: 2, text: "Rooms are too small."},
            {name: "Suresh Patil", rating: 5, text: "Loved the food and environment."},
            {name: "Anjali Shah", rating: 3, text: "Average, but good for students."},
            {name: "Rohit Kumar", rating: 1, text: "Noisy at night, couldn't sleep well."},
            {name: "Sneha Reddy", rating: 4, text: "Good value for money."},
            {name: "Vivek Singh", rating: 5, text: "Would stay again!"},
            {name: "Meena Joshi", rating: 3, text: "Decent, but can improve."},
            {name: "Tarun Mehta", rating: 5, text: "Great location and service."}
        ],
        5: [
            {name: "Aarti Sharma", rating: 5, text: "Very clean and well managed."},
            {name: "Ravi Kumar", rating: 4, text: "Good facilities and staff."},
            {name: "Neha Jain", rating: 2, text: "Food quality was not good."},
            {name: "Siddharth Singh", rating: 5, text: "Excellent experience!"},
            {name: "Pooja Patel", rating: 3, text: "Average, but safe."},
            {name: "Manoj Gupta", rating: 1, text: "Had issues with electricity."},
            {name: "Kavya Rao", rating: 4, text: "Nice rooms and friendly staff."},
            {name: "Rashmi Desai", rating: 5, text: "Would recommend to everyone."},
            {name: "Ajay Mehra", rating: 3, text: "Decent for the price."},
            {name: "Simran Kaur", rating: 5, text: "Loved my stay!"}
        ],
        6: [
            {name: "Rajat Malhotra", rating: 5, text: "Fantastic PG, very clean and peaceful."},
            {name: "Shilpa Rao", rating: 4, text: "Good food and friendly staff."},
            {name: "Aman Sethi", rating: 2, text: "Rooms are a bit cramped."},
            {name: "Nisha Patel", rating: 5, text: "Loved my stay, highly recommended!"},
            {name: "Gaurav Singh", rating: 3, text: "Average, but affordable."},
            {name: "Meena Kumari", rating: 1, text: "Had issues with the WiFi."},
            {name: "Rohit Das", rating: 4, text: "Nice rooms and good security."},
            {name: "Priya Mehra", rating: 5, text: "Would stay again!"},
            {name: "Sandeep Kaur", rating: 3, text: "Decent, but food can improve."},
            {name: "Vivek Sharma", rating: 5, text: "Best PG experience so far."}
        ],
        7: [
            {name: "Kavya Nair", rating: 5, text: "Very clean and well managed."},
            {name: "Ramesh Chawla", rating: 4, text: "Good facilities and staff."},
            {name: "Neha Sood", rating: 2, text: "Food quality was not good."},
            {name: "Siddharth Jain", rating: 5, text: "Excellent experience!"},
            {name: "Pooja Reddy", rating: 3, text: "Average, but safe."},
            {name: "Manoj Sinha", rating: 1, text: "Had issues with electricity."},
            {name: "Kavita Rao", rating: 4, text: "Nice rooms and friendly staff."},
            {name: "Rashmi Singh", rating: 5, text: "Would recommend to everyone."},
            {name: "Ajay Kumar", rating: 3, text: "Decent for the price."},
            {name: "Simran Joshi", rating: 5, text: "Loved my stay!"}
        ],
        8: [
            {name: "Aarti Mehra", rating: 5, text: "Amazing PG, very clean and well maintained."},
            {name: "Ritu Kapoor", rating: 4, text: "Good food and friendly staff."},
            {name: "Deepak Yadav", rating: 2, text: "Too far from main road."},
            {name: "Ankita Sinha", rating: 5, text: "Loved my stay here!"},
            {name: "Vikas Reddy", rating: 3, text: "Average, but affordable."},
            {name: "Megha Desai", rating: 1, text: "Had issues with cleanliness."},
            {name: "Ramesh Kumar", rating: 4, text: "Nice rooms and good security."},
            {name: "Pallavi Singh", rating: 5, text: "Highly recommended!"},
            {name: "Sanjana Mehra", rating: 3, text: "Decent, but food can improve."},
            {name: "Aakash Sethi", rating: 5, text: "Best PG experience so far."}
        ],
        9: [
            {name: "Kiran Patel", rating: 4, text: "Comfortable and safe."},
            {name: "Nitin Sood", rating: 5, text: "Excellent staff and amenities."},
            {name: "Priyanka Rao", rating: 2, text: "Rooms are too small."},
            {name: "Suresh Shah", rating: 5, text: "Loved the food and environment."},
            {name: "Anjali Mehta", rating: 3, text: "Average, but good for students."},
            {name: "Rohit Desai", rating: 1, text: "Noisy at night, couldn't sleep well."},
            {name: "Sneha Singh", rating: 4, text: "Good value for money."},
            {name: "Vivek Joshi", rating: 5, text: "Would stay again!"},
            {name: "Meena Sinha", rating: 3, text: "Decent, but can improve."},
            {name: "Tarun Kumar", rating: 5, text: "Great location and service."}
        ],
        10: [
            {name: "Aarti Sood", rating: 5, text: "Very clean and well managed."},
            {name: "Ravi Mehra", rating: 4, text: "Good facilities and staff."},
            {name: "Neha Reddy", rating: 2, text: "Food quality was not good."},
            {name: "Siddharth Desai", rating: 5, text: "Excellent experience!"},
            {name: "Pooja Sinha", rating: 3, text: "Average, but safe."},
            {name: "Manoj Patel", rating: 1, text: "Had issues with electricity."},
            {name: "Kavya Joshi", rating: 4, text: "Nice rooms and friendly staff."},
            {name: "Rashmi Shah", rating: 5, text: "Would recommend to everyone."},
            {name: "Ajay Sood", rating: 3, text: "Decent for the price."},
            {name: "Simran Mehra", rating: 5, text: "Loved my stay!"}
        ],
        // ... Repeat this pattern for property IDs 11 to 37, using unique names and review texts, ensuring 8-10 reviews per property and 2-3 negative reviews (rating 1 or 2) per property.
    };

    function getStarHtml(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (rating >= i) {
                html += '<i class="fas fa-star"></i>';
            } else if (rating >= i - 0.5) {
                html += '<i class="fas fa-star-half-alt"></i>';
            } else {
                html += '<i class="far fa-star"></i>';
            }
        }
        return html;
    }

    document.querySelectorAll('.view-reviews-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            const reviews = pgReviews[propertyId] || [];
            let html = '';
            if (reviews.length === 0) {
                html = '<p>No reviews available for this PG.</p>';
            } else {
                reviews.forEach(review => {
                    html += `<div class="review-item mb-3">
                        <div><strong>${review.name}</strong></div>
                        <div>${getStarHtml(review.rating)}</div>
                        <div>${review.text}</div>
                    </div><hr />`;
                });
            }
            document.getElementById('reviews-modal-body').innerHTML = html;
        });
    });
    </script>
</body>

</html>
