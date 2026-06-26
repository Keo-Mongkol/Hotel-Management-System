<?php
require_once '../config/database.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$editing = false;
$room = ['id' => '', 'room_number' => '', 'room_type_id' => '', 'floor' => '', 'status' => 'available', 'notes' => ''];

$stmt = $pdo->query("SELECT * FROM room_types ORDER BY type_name");
$room_types = $stmt->fetchAll();

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], 'Delete Room', "Deleted room ID: $id");
    redirect('rooms.php?msg=deleted');
}

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $editing = true;
        $room = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $room_number = $_POST['room_number'] ?? '';
    $room_type_id = $_POST['room_type_id'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $notes = $_POST['notes'] ?? '';

    if ($id) {
        $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type_id = ?, floor = ?, status = ?, notes = ? WHERE id = ?");
        $stmt->execute([$room_number, $room_type_id, $floor, $status, $notes, $id]);
        logActivity($_SESSION['user_id'], 'Update Room', "Updated room ID: $id");
        redirect('rooms.php?msg=updated');
    } else {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$room_number, $room_type_id, $floor, $status, $notes]);
        logActivity($_SESSION['user_id'], 'Add Room', "Added room number: $room_number");
        redirect('rooms.php?msg=added');
    }
}

$stmt = $pdo->query("SELECT r.*, rt.type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number");
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Rooms</h1>
                </div>

                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php
                        $messages = ['added'=>'Room added successfully.', 'updated'=>'Room updated successfully.', 'deleted'=>'Room deleted successfully.'];
                        echo $messages[$_GET['msg']] ?? 'Action completed.';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($room['id']); ?>">
                            <div class="col-md-3">
                                <label class="form-label">Room Number</label>
                                <input type="text" name="room_number" class="form-control" required value="<?php echo htmlspecialchars($room['room_number']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Room Type</label>
                                <select name="room_type_id" class="form-select" required>
                                    <option value="">Select type</option>
                                    <?php foreach($room_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $type['id'] == $room['room_type_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Floor</label>
                                <input type="number" name="floor" class="form-control" value="<?php echo htmlspecialchars($room['floor']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach(['available','occupied','maintenance','reserved'] as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $status == $room['status'] ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control"><?php echo htmlspecialchars($room['notes']); ?></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" name="save_room" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $editing ? 'Update' : 'Add'; ?> Room
                                </button>
                                <?php if($editing): ?>
                                    <a href="rooms.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Number</th>
                                        <th>Type</th>
                                        <th>Floor</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($rooms as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['floor']); ?></td>
                                        <td><?php echo ucfirst($row['status']); ?></td>
                                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                            <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this room?');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
