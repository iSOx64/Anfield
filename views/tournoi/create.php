<?php
$categories = isset($categories) && is_array($categories) ? $categories : [];
$levels = isset($levels) && is_array($levels) ? $levels : [];
$old = isset($old) && is_array($old) ? $old : [];
?>
<section class="section">
    <div class="card flow">
        <h2>Nouvelle competition</h2>
        <p>Definissez votre tournoi, son public et les informations pratiques pour faciliter les inscriptions.</p>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/tournoi" class="flow tournoi-form">
            <div class="form-grid form-grid--two">
                <div>
                    <label for="nom">Nom du tournoi</label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        required
                        value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                        placeholder="Foot Summer Cup"
                    >
                </div>
                <div>
                    <label for="format">Format</label>
                    <select id="format" name="format" class="form-control">
                        <option value="8" <?= ($old['format'] ?? '8') === '8' ? 'selected' : '' ?>>8 equipes</option>
                        <option value="16" <?= ($old['format'] ?? '') === '16' ? 'selected' : '' ?>>16 equipes</option>
                    </select>
                </div>
            </div>

            <div class="form-grid form-grid--two">
                <div>
                    <label for="categorie">Categorie</label>
                    <select id="categorie" name="categorie" class="form-control">
                        <option value="">Tous publics</option>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"
                                <?= ($old['categorie'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="niveau">Niveau</label>
                    <select id="niveau" name="niveau" class="form-control">
                        <option value="">Multi-niveaux</option>
                        <?php foreach ($levels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"
                                <?= ($old['niveau'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid form-grid--two">
                <div>
                    <label for="date_debut">Date de debut</label>
                    <input
                        type="date"
                        id="date_debut"
                        name="date_debut"
                        value="<?= htmlspecialchars($old['date_debut'] ?? '') ?>"
                    >
                </div>
                <div>
                    <label for="date_fin">Date de fin</label>
                    <input
                        type="date"
                        id="date_fin"
                        name="date_fin"
                        value="<?= htmlspecialchars($old['date_fin'] ?? '') ?>"
                    >
                </div>
            </div>

            <div>
                <label for="lieu">Lieu</label>
                <input
                    type="text"
                    id="lieu"
                    name="lieu"
                    value="<?= htmlspecialchars($old['lieu'] ?? '') ?>"
                    placeholder="Complexe Foot Fields, Casablanca"
                >
            </div>

            <div class="form-grid form-grid--two">
                <div>
                    <label for="frais_inscription">Frais d'inscription</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="frais_inscription"
                        name="frais_inscription"
                        value="<?= htmlspecialchars($old['frais_inscription'] ?? '') ?>"
                        placeholder="Ex: 150.00"
                    >
                </div>
                <div>
                    <label for="recompense">Recompense</label>
                    <input
                        type="text"
                        id="recompense"
                        name="recompense"
                        value="<?= htmlspecialchars($old['recompense'] ?? '') ?>"
                        placeholder="Trophee, lots partenaires..."
                    >
                </div>
            </div>

            <div>
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Precisez le format, les regles ou les animations prevues"
                ><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
            </div>

            <div class="form-grid form-grid--two">
                <div>
                    <label for="contact_email">Contact e-mail</label>
                    <input
                        type="email"
                        id="contact_email"
                        name="contact_email"
                        value="<?= htmlspecialchars($old['contact_email'] ?? '') ?>"
                        placeholder="contact@monclub.com"
                    >
                </div>
                <div>
                    <label for="contact_phone">Contact telephone</label>
                    <input
                        type="tel"
                        id="contact_phone"
                        name="contact_phone"
                        value="<?= htmlspecialchars($old['contact_phone'] ?? '') ?>"
                        placeholder="+212 600 00 00 00"
                    >
                </div>
            </div>

            <button type="submit" class="btn btn--primary">Creer le tournoi</button>
        </form>
    </div>
</section>
