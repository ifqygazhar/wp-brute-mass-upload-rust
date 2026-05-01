<div style="margin-top: 10px;display: inline-block;">
    <label for="cf7cstmzr-preview-mode" style="display: none;"><?php _e( 'Preview mode', 'cf7-styler' ) ?></label>

    <select id="cf7cstmzr-preview-mode" class="cf7cstmzr-form-control" style="display: none;max-width: 150px;">
        <option value="split-mode"<?php echo $preview_mode === 'split-mode' ? ' selected' : ''; ?>><?php _e( 'Split mode', 'cf7-styler' ) ?></option>
        <option value="current-style"<?php echo $preview_mode === 'current-style' ? ' selected' : ''; ?>><?php _e( 'Current Style', 'cf7-styler' ) ?></option>
        <option value="live-style"<?php echo $preview_mode === 'live-style' ? ' selected' : ''; ?>><?php _e( 'Live', 'cf7-styler' ) ?></option>
        <option value="unstyled"<?php echo $preview_mode === 'unstyled' ? ' selected' : ''; ?>><?php _e( 'Unstyled', 'cf7-styler' ) ?></option>
    </select>


    <label for="cf7cstmzr-preview-mode-check" style="display: inline-block;height: 25px;line-height: 25px;"><?php _e( 'Split view', 'cf7-styler' ) ?></label>

    <input type="checkbox" name="cf7cstmzr-split-fixed" value="split-view-check" id="cf7cstmzr-preview-mode-check" checked>

    <span id="split-mode-settings" style="display: <?php echo 'split-mode' === $preview_mode ? 'inline-block' : 'none'; ?>; margin-left: 10px;vertical-align: middle;">
        <span style="display: inline-block;line-height: 25px;vertical-align: middle;"><?php _e( 'Second column view', 'cf7-styler' ) ?>:</span>
        <label for="cf7cstmzr-split-live-style" style="display: inline-block; margin-left: 10px;height: 25px;line-height: 25px;vertical-align: middle;">
            <?php _e( 'Live', 'cf7-styler' ) ?>
        </label>

        <input type="radio" name="cf7cstmzr-split-mode" value="live-style"
               id="cf7cstmzr-split-live-style"<?php echo empty( $split_mode ) || 'live-style' === $split_mode ? ' checked' : ''; ?>>

        <label for="cf7cstmzr-split-live-unstyled" style="display: inline-block; margin-left: 5px;height: 25px;line-height: 25px;vertical-align: middle;">
            <?php _e( 'Unstyled', 'cf7-styler' ) ?>
        </label>
        <input type="radio" name="cf7cstmzr-split-mode" value="split-unstyled"
               id="cf7cstmzr-split-live-unstyled"<?php echo ! empty( $split_mode ) && 'split-unstyled' === $split_mode ? ' checked' : ''; ?>>

        <label for="cf7cstmzr-split-fixed" style="display: inline-block; margin-left: 10px;height: 25px;line-height: 25px;vertical-align: middle;">
            <?php _e( 'Duplicate form in second column', 'cf7-styler' ) ?>
        </label>
        <input type="checkbox" name="cf7cstmzr-split-fixed" value="split-fixed" id="cf7cstmzr-split-fixed">
    </span>
</div>