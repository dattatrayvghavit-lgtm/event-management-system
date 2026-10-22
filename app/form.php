<?php
declare(strict_types=1);
function input(string $name,string $label,string $type='text',mixed $value='',string $extra=''): void { ?>
<label><?=e($label)?><input name="<?=e($name)?>" type="<?=e($type)?>" value="<?=e($value)?>" <?=$extra?>></label>
<?php }
function badge(string $label): string { return '<span class="badge '.e(strtolower($label)).'">'.e(ucfirst($label)).'</span>'; }
