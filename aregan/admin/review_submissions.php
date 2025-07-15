<?php
// This is a protected page.
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// --- Handle Actions (Approve/Reject) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = $_POST['submission_id'];
    
    // ACTION: APPROVE
    if (isset($_POST['approve'])) {
        // 1. Fetch the submission data
        $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ?");
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch();

        if ($submission) {
            // 2. Insert into the main 'events' table
            $sql = "INSERT INTO events (event_name, event_details, event_date, category, latitude, longitude, radius, icon_class, images, sources)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $submission['event_name'], $submission['event_details'], $submission['event_date'],
                $submission['category'], $submission['latitude'], $submission['longitude'],
                $submission['radius'], $submission['icon_class'], $submission['images'], $submission['sources']
            ]);

            // 3. Update the submission status to 'approved'
            $stmt = $pdo->prepare("UPDATE submissions SET status = 'approved' WHERE id = ?");
            $stmt->execute([$submission_id]);
            $message = "Laporan #$submission_id telah disetujui dan dipublikasikan.";
        }
    }
    // ACTION: REJECT
    elseif (isset($_POST['reject'])) {
        $stmt = $pdo->prepare("UPDATE submissions SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$submission_id]);
        $message = "Laporan #$submission_id telah ditolak.";
    }
}

// --- Fetch all pending submissions ---
$stmt = $pdo->query("SELECT * FROM submissions WHERE status = 'pending' ORDER BY submitted_at DESC");
$pending_submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tinjau Laporan - AREGAN Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>.accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }</style>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Tinjau Laporan Masuk</h1>
            <a href="index.php" class="text-blue-600 hover:text-blue-800">← Kembali ke Dashboard</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <?php if (empty($pending_submissions)): ?>
                <p class="p-6 text-center text-gray-500">Tidak ada laporan yang menunggu untuk ditinjau saat ini.</p>
            <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($pending_submissions as $sub): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-bold text-blue-600 truncate"><?php echo htmlspecialchars($sub['event_name']); ?></p>
                                    <div class="ml-2 flex-shrink-0 flex">
                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 sm:flex sm:justify-between">
                                    <div class="sm:flex">
                                        <p class="flex items-center text-sm text-gray-500"><i class="fas fa-calendar-alt mr-2"></i> <?php echo date("d M Y", strtotime($sub['event_date'])); ?></p>
                                        <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6"><i class="fas fa-tag mr-2"></i> <?php echo htmlspecialchars($sub['category']); ?></p>
                                    </div>
                                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                        <button class="accordion-toggle text-blue-500 hover:underline">Lihat Detail ↓</button>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-content border-t border-gray-200">
                                <div class="bg-gray-50 px-4 py-5 sm:p-6">
                                    <h3 class="text-base font-semibold text-gray-900">Detail Laporan</h3>
                                    <p class="mt-2 text-sm text-gray-600 whitespace-pre-wrap"><?php echo htmlspecialchars($sub['event_details']); ?></p>
                                    
                                    <dl class="mt-4 grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                        <div class="sm:col-span-1"><dt class="text-sm font-medium text-gray-500">Koordinat</dt><dd class="mt-1 text-sm text-gray-900"><?php echo "{$sub['latitude']}, {$sub['longitude']}"; ?></dd></div>
                                        <div class="sm:col-span-1"><dt class="text-sm font-medium text-gray-500">Radius</dt><dd class="mt-1 text-sm text-gray-900"><?php echo $sub['radius'] ? htmlspecialchars($sub['radius']).'m' : 'N/A'; ?></dd></div>
                                        <div class="sm:col-span-1"><dt class="text-sm font-medium text-gray-500">Pelapor</dt><dd class="mt-1 text-sm text-gray-900"><?php echo $sub['submitter_name'] ? htmlspecialchars($sub['submitter_name']) : 'Anonim'; ?></dd></div>
                                        <div class="sm:col-span-1"><dt class="text-sm font-medium text-gray-500">Email</dt><dd class="mt-1 text-sm text-gray-900"><?php echo $sub['submitter_email'] ? htmlspecialchars($sub['submitter_email']) : 'N/A'; ?></dd></div>
                                    </dl>

                                    <div class="mt-5 flex justify-end gap-3">
                                        <form method="POST" action="review_submissions.php" onsubmit="return confirm('Apakah Anda yakin ingin menolak laporan ini?');">
                                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" name="reject" class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Tolak</button>
                                        </form>
                                        <form method="POST" action="review_submissions.php" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dan mempublikasikan laporan ini?');">
                                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" name="approve" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">Setujui & Publikasikan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
    <script>
    document.querySelectorAll('.accordion-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const content = button.closest('li').querySelector('.accordion-content');
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                button.innerHTML = 'Lihat Detail ↓';
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
                button.innerHTML = 'Sembunyikan Detail ↑';
            } 
        });
    });
    </script>
</body>
</html>