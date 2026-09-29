<?php
$pageTitle='Usuários'; require_once __DIR__ . '/includes/header.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $id=(int)($_POST['id']??0); $usuario=trim($_POST['usuario']??''); $nome=trim($_POST['nome']??''); $senha=$_POST['senha']??''; $tipo=$_POST['tipo']==='admin'?'admin':'normal';
        if($usuario===''||$nome==='') throw new RuntimeException('Preencha usuário e nome.');
        if(!$id && strlen($senha)<6) throw new RuntimeException('Informe uma senha com pelo menos 6 caracteres.');
        if($senha!==''&&strlen($senha)<6) throw new RuntimeException('A senha deve ter pelo menos 6 caracteres.');
        if($id === (int)$_SESSION['usuario_id']) { $current=$pdo->prepare('SELECT tipo FROM usuarios WHERE id=?'); $current->execute([$id]); $tipo=$current->fetchColumn(); }
        $exists=$pdo->prepare('SELECT id FROM usuarios WHERE usuario=? AND id<>?'); $exists->execute([$usuario,$id]);
        if($exists->fetch()) throw new RuntimeException('Já existe um usuário com esse login.');
        $before = $id ? history_snapshot($pdo, 'usuario', $id) : [];
        if($id) {
            if($senha!=='') $pdo->prepare('UPDATE usuarios SET usuario=?,nome=?,tipo=?,senha_hash=? WHERE id=?')->execute([$usuario,$nome,$tipo,password_hash($senha,PASSWORD_DEFAULT),$id]);
            else $pdo->prepare('UPDATE usuarios SET usuario=?,nome=?,tipo=? WHERE id=?')->execute([$usuario,$nome,$tipo,$id]);
            flash('success','Usuário atualizado com sucesso.');
        } else {
            $pdo->prepare('INSERT INTO usuarios (usuario,senha_hash,nome,tipo) VALUES (?,?,?,?)')->execute([$usuario,password_hash($senha,PASSWORD_DEFAULT),$nome,$tipo]);
            $id = (int) $pdo->lastInsertId();
            flash('success','Usuário criado com sucesso.');
        }
        $passwordChange = $senha !== '' ? [['campo' => 'Senha', 'antes' => '', 'depois' => $before ? '(alterada)' : '(definida)']] : [];
        history_log($pdo, 'usuario', $id, $before, history_snapshot($pdo, 'usuario', $id), $passwordChange);
        redirect('usuarios.php');
    } catch(Throwable $e) { flash('error',$e->getMessage()); }
}
if (isset($_GET['toggle'])) {
    $toggleId=(int)$_GET['toggle'];
    if ($toggleId === (int)$_SESSION['usuario_id']) { flash('error','Você não pode ocultar o próprio usuário.'); }
    else { $before=history_snapshot($pdo,'usuario',$toggleId); $pdo->prepare("UPDATE usuarios SET status=IF(status='ativo','oculto','ativo') WHERE id=?")->execute([$toggleId]); history_log($pdo,'usuario',$toggleId,$before,history_snapshot($pdo,'usuario',$toggleId)); flash('success','Status atualizado.'); }
    redirect('usuarios.php'.(isset($_GET['status'])?'?status='.urlencode($_GET['status']):''));
}
$editUser=['id'=>'','usuario'=>'','nome'=>'','tipo'=>'normal'];
if (isset($_GET['editar'])) { $s=$pdo->prepare('SELECT * FROM usuarios WHERE id=?'); $s->execute([(int)$_GET['editar']]); $editUser=$s->fetch() ?: $editUser; }
$statusFilter = ($_GET['status']??'') === 'oculto' ? 'oculto' : 'ativo';
$users=$pdo->prepare('SELECT * FROM usuarios WHERE status=? ORDER BY nome'); $users->execute([$statusFilter]); $users=$users->fetchAll();
?><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Usuários</h1><p class="text-muted mb-0">Gerencie quem pode acessar o sistema</p></div></div><div class="row g-4"><div class="col-lg-4"><section class="app-card"><h2 class="h5 mb-3"><?= $editUser['id']?'Editar usuário':'Novo usuário' ?></h2><form method="post"><input type="hidden" name="id" value="<?= e($editUser['id']) ?>"><div class="mb-3"><label class="form-label">Nome</label><input class="form-control" name="nome" required value="<?= e($editUser['nome']) ?>"></div><div class="mb-3"><label class="form-label">Usuário (login)</label><input class="form-control" name="usuario" required value="<?= e($editUser['usuario']) ?>"></div><div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="senha" <?= $editUser['id']?'':'required' ?> minlength="6" placeholder="<?= $editUser['id']?'Deixe em branco para manter a atual':'' ?>"></div><div class="mb-4"><label class="form-label">Tipo de acesso</label><select class="form-select" name="tipo" <?= $editUser['id']&&(int)$editUser['id']===(int)$_SESSION['usuario_id']?'disabled':'' ?>><option value="normal" <?= $editUser['tipo']==='normal'?'selected':'' ?>>Normal</option><option value="admin" <?= $editUser['tipo']==='admin'?'selected':'' ?>>Administrador</option></select><?php if($editUser['id']&&(int)$editUser['id']===(int)$_SESSION['usuario_id']): ?><small class="text-muted d-block mt-1">Você não pode alterar o próprio tipo de acesso.</small><?php endif; ?></div><button class="btn btn-gold" type="submit"><?= $editUser['id']?'Salvar alterações':'Criar usuário' ?></button><?php if($editUser['id']): ?><a class="btn btn-outline-secondary ms-2" href="usuarios.php">Cancelar</a><?php endif; ?></form></section></div><div class="col-lg-8"><section class="app-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Usuários cadastrados</h2><div class="btn-group"><a class="btn btn-sm <?= $statusFilter==='ativo'?'btn-navy':'btn-outline-secondary' ?>" href="usuarios.php?status=ativo">Ativos</a><a class="btn btn-sm <?= $statusFilter==='oculto'?'btn-navy':'btn-outline-secondary' ?>" href="usuarios.php?status=oculto">Ocultos</a></div></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nome</th><th>Usuário</th><th>Tipo</th><th></th></tr></thead><tbody><?php if(!$users): ?><tr><td class="text-center text-muted py-4" colspan="4">Nenhum usuário <?= $statusFilter==='ativo'?'ativo':'oculto' ?>.</td></tr><?php endif; ?><?php foreach($users as $u): ?><tr><td><?= e($u['nome']) ?></td><td><?= e($u['usuario']) ?></td><td><span class="badge text-bg-<?= $u['tipo']==='admin'?'warning':'secondary' ?>"><?= $u['tipo']==='admin'?'Administrador':'Normal' ?></span></td><td class="text-end"><div class="group-actions"><a class="btn btn-sm btn-action btn-action-edit" href="usuarios.php?editar=<?= $u['id'] ?>&status=<?= $statusFilter ?>">Editar</a><?php if((int)$u['id']!==(int)$_SESSION['usuario_id']): ?><a class="btn btn-sm btn-action btn-action-hide" href="usuarios.php?toggle=<?= $u['id'] ?>&status=<?= $statusFilter ?>"><?= $u['status']==='ativo'?'Ocultar':'Ativar' ?></a><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></section></div></div><?php require __DIR__ . '/includes/footer.php';
