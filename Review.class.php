<?php
// Review.class.php - PHP class for Review object
class Review {
    private $id;
    private $patientName;
    private $rating;
    private $comments;
    private $recommend;
    private $createdAt;
    
    // Constructor
    public function __construct($id, $name, $rating, $comments, $recommend, $createdAt) {
        $this->id = $id;
        $this->patientName = $name;
        $this->rating = $rating;
        $this->comments = $comments;
        $this->recommend = $recommend;
        $this->createdAt = $createdAt;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getPatientName() { return $this->patientName; }
    public function getRating() { return $this->rating; }
    public function getComments() { return $this->comments; }
    public function getRecommend() { return $this->recommend; }
    public function getCreatedAt() { return $this->createdAt; }
    
    //Setters 
    public function setPatientName($name) { 
        $this->patientName = $name; }
        
    // Method to get star rating
    public function getStarRating() {
        $stars = '';
        for ($i = 0; $i < 5; $i++) {
            $stars .= ($i < $this->rating) ? '★' : '☆';
        }
        return $stars;
    }
    
    // Display as table row
    public function displayAsTableRow() {
        return "<tr>
            <td>" . htmlspecialchars($this->patientName) . "</td>
            <td>" . $this->getStarRating() . "</td>
            <td>" . htmlspecialchars($this->comments) . "</td>
            <td>" . htmlspecialchars($this->recommend) . "</td>
            <td>" . $this->createdAt . "</td>
        </tr>";
    }
}

// Function to display array of reviews as table
function displayReviewsTable($reviewsArray) {
    $html = "<table class='table table-striped'>
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Rating</th>
                <th>Comments</th>
                <th>Recommend</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($reviewsArray as $review) {
        $html .= $review->displayAsTableRow();
    }
    
    $html .= "</tbody></table>";
    return $html;
}
?>