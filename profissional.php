<?php
session_start();
require_once("config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_nivel'] != 2 && $_SESSION['usuario_nivel'] != 3)) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$usuario_nivel = $_SESSION['usuario_nivel'];
$profissionais_para_exibir = [];

if ($usuario_nivel == 3) {
    $stmt_distinct_prof_ids = $conn->prepare("SELECT DISTINCT profissional_id FROM agenda WHERE profissional_id IS NOT NULL");
    if ($stmt_distinct_prof_ids) {
        $stmt_distinct_prof_ids->execute();
        $res_distinct_prof_ids = $stmt_distinct_prof_ids->get_result();
        while ($row = $res_distinct_prof_ids->fetch_assoc()) {
            $prof_id = $row['profissional_id'];
            $stmt_prof_nome = $conn->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND nivel = 2");
            if ($stmt_prof_nome) {
                $stmt_prof_nome->bind_param("i", $prof_id);
                $stmt_prof_nome->execute();
                $res_prof_nome = $stmt_prof_nome->get_result();
                if ($prof_data = $res_prof_nome->fetch_assoc()) {
                    $profissionais_para_exibir[] = ['id' => $prof_data['id'], 'nome' => $prof_data['nome']];
                }
                $stmt_prof_nome->close();
            }
        }
        $stmt_distinct_prof_ids->close();
    }
} else {
    $profissionais_para_exibir[] = ['id' => $usuario_id, 'nome' => $usuario_nome];
}

function buscarAgendamentos($conn, $profissional_id, $tipo = 'futuro') {
    $agendamentos = [];
    $data_atual = date('Y-m-d');

    $sql = $tipo == 'futuro'
        ? "SELECT a.id, a.servico, a.data, a.hora, a.preco, a.status_pagamento, a.observacoes,
                  u.nome AS nome_cliente, p.nome AS nome_profissional_agenda
             FROM agenda a
             JOIN usuarios u ON a.usuario = u.id
             JOIN usuarios p ON a.profissional_id = p.id
             WHERE a.profissional_id = ? AND a.data >= ?
             ORDER BY a.data ASC, a.hora ASC"
        : "SELECT a.id, a.servico, a.data, a.hora, a.preco, a.status_pagamento, a.observacoes,
                  u.nome AS nome_cliente, p.nome AS nome_profissional_agenda
             FROM agenda a
             JOIN usuarios u ON a.usuario = u.id
             JOIN usuarios p ON a.profissional_id = p.id
             WHERE a.profissional_id = ? AND a.data < ?
             ORDER BY a.data DESC, a.hora DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $profissional_id, $data_atual);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $agendamentos[] = $row;
            }
        }
        $stmt->close();
    }
    return $agendamentos;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $usuario_nivel == 3) {
    if (isset($_POST['agendamento_id']) && isset($_POST['status'])) {
        $agendamento_id = intval($_POST['agendamento_id']);
        $novo_status = $_POST['status'];

        $stmt_update = $conn->prepare("UPDATE agenda SET status_pagamento = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $novo_status, $agendamento_id);
            $stmt_update->execute();
            $stmt_update->close();
            header("Location: profissional.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Profissional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff0f5;
        }
        .bg-topo {
            background-color: #ffb6c1;
        }
        .btn-primary {
            background-color: #ff69b4;
            border-color: #ff69b4;
        }
        .btn-primary:hover {
            background-color: #ff1493;
            border-color: #ff1493;
        }
        .card {
            border: 1px solid #f7cac9;
        }
        .table-light {
            background-color: #ffe4e1;
        }
        .badge-success {
            background-color: #c2185b;
        }
        .badge-warning {
            background-color: #f48fb1;
        }
    </style>
</head>
<body>
<div class="bg-topo text-white py-3 px-4 d-flex justify-content-between align-items-center">
    <h3 class="m-0">Bem-vindo, <?= htmlspecialchars($usuario_nome) ?></h3>
    <a href="logout.php" class="btn btn-danger">Sair</a>
</div>

<div class="container mt-4">
    <?php if (empty($profissionais_para_exibir)): ?>
        <div class="alert alert-warning">Nenhum profissional encontrado ou você não tem agendamentos.</div>
    <?php endif; ?>

    <?php foreach ($profissionais_para_exibir as $prof_info): ?>
        <div class="card mb-5 shadow-sm">
            <div class="card-body">
                <h4 class="card-title">Profissional: <?= htmlspecialchars($prof_info['nome']) ?></h4>

                <h5 class="mt-4">Horários Agendados</h5>
                <?php $futuros = buscarAgendamentos($conn, $prof_info['id'], 'futuro'); ?>
                <?php if (!empty($futuros)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Serviço</th>
                                    <th>Preço</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($futuros as $agendamento): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($agendamento['nome_cliente']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($agendamento['data'])) ?></td>
                                        <td><?= htmlspecialchars($agendamento['hora']) ?></td>
                                        <td><?= htmlspecialchars($agendamento['servico']) ?></td>
                                        <td>R$ <?= number_format($agendamento['preco'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Não há horários agendados futuros.</p>
                <?php endif; ?>

                <h5 class="mt-4">Histórico de Atendimentos</h5>
                <?php $historico = buscarAgendamentos($conn, $prof_info['id'], 'passado'); $totalComissao = 0; ?>
                <?php if (!empty($historico)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Serviço</th>
                                    <th>Preço</th>
                                    <th>Comissão (60%)</th>
                                    <th>Status</th>
                                    <?php if ($usuario_nivel == 3): ?><th>Ações</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $agendamento): 
                                    $comissao = $agendamento['preco'] * 0.60;
                                    $totalComissao += $comissao;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($agendamento['nome_cliente']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($agendamento['data'])) ?></td>
                                        <td><?= htmlspecialchars($agendamento['hora']) ?></td>
                                        <td><?= htmlspecialchars($agendamento['servico']) ?></td>
                                        <td>R$ <?= number_format($agendamento['preco'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($comissao, 2, ',', '.') ?></td>
                                        <td><span class="badge bg-<?= $agendamento['status_pagamento'] === 'pago' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($agendamento['status_pagamento']) ?></span></td>
                                        <?php if ($usuario_nivel == 3): ?>
                                            <td>
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                                                    <input type="hidden" name="status" value="pago">
                                                    <button class="btn btn-sm btn-success" <?= $agendamento['status_pagamento'] === 'pago' ? 'disabled' : '' ?>>Pago</button>
                                                </form>
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                                                    <input type="hidden" name="status" value="aguardando">
                                                    <button class="btn btn-sm btn-warning" <?= $agendamento['status_pagamento'] === 'aguardando' ? 'disabled' : '' ?>>Aguardando</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success">
                                    <td colspan="5"><strong>Total de Comissão (60%)</strong></td>
                                    <td colspan="<?= ($usuario_nivel == 3) ? '3' : '2' ?>"><strong>R$ <?= number_format($totalComissao, 2, ',', '.') ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Não há atendimentos anteriores.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>