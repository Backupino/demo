<?php
/**
 * Operator Portal: SLA Timers & Support Tickets Inbox View.
 *
 * Implements S3-T09 and S3-T07.
 *
 * @var array $queue
 * @var array $tickets_data
 * @var string $nonce
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$tkt_items = $tickets_data['items'] ?? array();
$tkt_stats = $tickets_data['stats'] ?? array();
$current_status = sanitize_text_field( $_GET['status'] ?? 'all' );
?>

<div class="wrap backupino-admin-wrap" style="max-width: 100%; margin-inline-end: 20px; box-sizing: border-box; margin-top: 20px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-clock" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7; vertical-align: middle;"></span>
		<?php esc_html_e( 'SLA Timers & Customer Support Tickets', 'backupino-portal' ); ?>
	</h1>

	<!-- Strict D-042 Security Invariant Banner -->
	<div class="notice notice-info" style="border-inline-start-color: #0284c7; padding: 12px 16px; margin-block: 16px; max-width: 100%;">
		<p style="margin: 0; font-size: 13px;">
			<strong>🛡️ <?php esc_html_e( 'Support Policy & Boundaries (D-042):', 'backupino-portal' ); ?></strong>
			<?php esc_html_e( 'Support agents assist clients in configuration, billing, and troubleshooting. Customer data is strictly private and operators have no authority to initiate restores on client sites.', 'backupino-portal' ); ?>
		</p>
	</div>

	<!-- Plan SLA Timers Reference Bar -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
		<div style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; border-inline-start: 4px solid #94a3b8; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<strong style="color: #64748b; font-size: 12px;"><?php esc_html_e( 'TRIAL TIER', 'backupino-portal' ); ?></strong>
			<div style="font-size: 16px; font-weight: 700; margin-top: 4px; color: #0f172a;"><?php esc_html_e( 'No Guaranteed SLA', 'backupino-portal' ); ?></div>
		</div>

		<div style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; border-inline-start: 4px solid #0284c7; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<strong style="color: #0284c7; font-size: 12px;"><?php esc_html_e( 'PERSONAL TIER', 'backupino-portal' ); ?></strong>
			<div style="font-size: 16px; font-weight: 700; margin-top: 4px; color: #0f172a;"><?php esc_html_e( '48 Hours Response', 'backupino-portal' ); ?></div>
		</div>

		<div style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; border-inline-start: 4px solid #0891b2; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<strong style="color: #0891b2; font-size: 12px;"><?php esc_html_e( 'FREELANCER TIER', 'backupino-portal' ); ?></strong>
			<div style="font-size: 16px; font-weight: 700; margin-top: 4px; color: #0f172a;"><?php esc_html_e( '24 Hours Response', 'backupino-portal' ); ?></div>
		</div>

		<div style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; border-inline-start: 4px solid #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<strong style="color: #dc2626; font-size: 12px;"><?php esc_html_e( 'AGENCY TIER', 'backupino-portal' ); ?></strong>
			<div style="font-size: 16px; font-weight: 700; margin-top: 4px; color: #0f172a;"><?php esc_html_e( '12 Hours (Dedicated)', 'backupino-portal' ); ?></div>
		</div>
	</div>

	<!-- SECTION: Customer Support Tickets Inbox -->
	<div class="bk-admin-card" style="width: 100%; max-width: 100%; box-sizing: border-box; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
			<h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
				🎫 <?php esc_html_e( 'Customer Support Tickets & SLA Queue', 'backupino-portal' ); ?>
			</h2>

			<!-- Filter tabs -->
			<div style="display: flex; gap: 8px;">
				<a href="<?php echo esc_url( add_query_arg( 'status', 'all' ) ); ?>" class="button <?php echo 'all' === $current_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'All', 'backupino-portal' ); ?> (<?php echo (int) ( $tickets_data['total'] ?? 0 ); ?>)
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'open' ) ); ?>" class="button <?php echo 'open' === $current_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Open', 'backupino-portal' ); ?> (<?php echo (int) ( $tkt_stats['open'] ?? 0 ); ?>)
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'in_progress' ) ); ?>" class="button <?php echo 'in_progress' === $current_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'In Progress', 'backupino-portal' ); ?> (<?php echo (int) ( $tkt_stats['in_progress'] ?? 0 ); ?>)
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'waiting_customer' ) ); ?>" class="button <?php echo 'waiting_customer' === $current_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Waiting Customer', 'backupino-portal' ); ?> (<?php echo (int) ( $tkt_stats['waiting_customer'] ?? 0 ); ?>)
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'resolved' ) ); ?>" class="button <?php echo 'resolved' === $current_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Resolved / Closed', 'backupino-portal' ); ?> (<?php echo (int) ( $tkt_stats['resolved'] ?? 0 ); ?>)
				</a>
			</div>
		</div>

		<div style="width: 100%; overflow-x: auto;">
			<table class="bk-modern-table" style="width: 100%;">
				<thead>
					<tr>
						<th style="width: 140px;"><?php esc_html_e( 'Ticket #', 'backupino-portal' ); ?></th>
						<th style="min-width: 160px;"><?php esc_html_e( 'Customer / Email', 'backupino-portal' ); ?></th>
						<th style="min-width: 200px;"><?php esc_html_e( 'Subject', 'backupino-portal' ); ?></th>
						<th style="width: 120px;"><?php esc_html_e( 'Priority', 'backupino-portal' ); ?></th>
						<th style="width: 130px;"><?php esc_html_e( 'Status', 'backupino-portal' ); ?></th>
						<th style="width: 160px;"><?php esc_html_e( 'SLA Deadline', 'backupino-portal' ); ?></th>
						<th style="width: 140px;"><?php esc_html_e( 'Created At', 'backupino-portal' ); ?></th>
						<th style="width: 130px; text-align: center;"><?php esc_html_e( 'Actions', 'backupino-portal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $tkt_items ) ) : ?>
						<tr>
							<td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">
								<?php esc_html_e( 'No tickets found in this status.', 'backupino-portal' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $tkt_items as $t ) : ?>
							<tr>
								<td>
									<strong style="color: #0284c7;"><?php echo esc_html( $t['ticket_number'] ); ?></strong>
								</td>
								<td>
									<strong><?php echo esc_html( $t['customer_name'] ); ?></strong><br>
									<span style="font-size: 11px; color: #64748b;"><?php echo esc_html( $t['customer_email'] ); ?></span>
								</td>
								<td>
									<span style="font-weight: 600;"><?php echo esc_html( $t['subject'] ); ?></span>
									<?php if ( (int) $t['replies_count'] > 1 ) : ?>
										<span style="background: #e2e8f0; color: #334155; font-size: 11px; padding: 2px 6px; border-radius: 9999px; margin-inline-start: 4px;">
											<?php echo (int) $t['replies_count']; ?> <?php esc_html_e( 'messages', 'backupino-portal' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( 'critical_dedicated' === $t['priority'] ) : ?>
										<span style="background: #fee2e2; color: #b91c1c; font-weight: 700; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
											🔥 VIP Agency
										</span>
									<?php elseif ( 'high' === $t['priority'] ) : ?>
										<span style="background: #e0f2fe; color: #0369a1; font-weight: 700; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
											⚡ Freelancer
										</span>
									<?php else : ?>
										<span style="background: #f1f5f9; color: #475569; font-weight: 600; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
											Standard
										</span>
									<?php endif; ?>
								</td>
								<td>
									<?php
									$st = $t['status'];
									$st_label = match ( $st ) {
										'open' => __( 'Open', 'backupino-portal' ),
										'in_progress' => __( 'In Progress', 'backupino-portal' ),
										'waiting_customer' => __( 'Waiting Customer', 'backupino-portal' ),
										'resolved' => __( 'Resolved', 'backupino-portal' ),
										'closed' => __( 'Closed', 'backupino-portal' ),
										default => $st,
									};
									$st_color = match ( $st ) {
										'open' => '#1d4ed8; background: #dbeafe',
										'in_progress' => '#b45309; background: #fef3c7',
										'waiting_customer' => '#b91c1c; background: #fee2e2',
										'resolved', 'closed' => '#475569; background: #f1f5f9',
										default => '#334155; background: #f1f5f9',
									};
									?>
									<span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; color: <?php echo esc_attr( $st_color ); ?>;">
										<?php echo esc_html( $st_label ); ?>
									</span>
								</td>
								<td>
									<?php if ( $t['sla_breached'] ) : ?>
										<span style="background: #ef4444; color: #ffffff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700;">
											⚠️ <?php esc_html_e( 'SLA Breached', 'backupino-portal' ); ?>
										</span>
									<?php else : ?>
										<div style="font-size: 12px; color: #334155;">
											<?php echo esc_html( $t['sla_deadline_at'] ); ?>
										</div>
										<?php if ( $t['sla_remaining_seconds'] > 0 && in_array( $t['status'], array( 'open', 'in_progress' ), true ) ) : ?>
											<span style="font-size: 11px; color: #16a34a; font-weight: 600;">
												⏱️ <?php echo esc_html( $t['sla_remaining_formatted'] ); ?> <?php esc_html_e( 'left', 'backupino-portal' ); ?>
											</span>
										<?php endif; ?>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( substr( (string) $t['created_at'], 0, 16 ) ); ?>
								</td>
								<td style="text-align: center;">
									<button type="button" class="button button-small" onclick="bkAdminOpenTicket(<?php echo (int) $t['id']; ?>)">
										💬 <?php esc_html_e( 'View & Reply', 'backupino-portal' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Modal: Operator Ticket View & Reply -->
	<div id="bk-operator-ticket-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); z-index: 100000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
		<div style="background: #fff; width: 100%; max-width: 780px; max-height: 90vh; overflow-y: auto; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); padding: 24px; position: relative; box-sizing: border-box;">
			<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 16px;">
				<div>
					<h3 id="op-modal-title" style="margin: 0; font-size: 18px; color: #0f172a;"></h3>
					<span id="op-modal-meta" style="font-size: 12px; color: #0284c7; font-weight: 600;"></span>
				</div>
				<div style="display: flex; gap: 8px; align-items: center;">
					<button type="button" class="button button-link-delete" onclick="bkAdminDirectCloseTicket();" style="color: #dc2626; font-size: 12px;"><?php esc_html_e( 'Close Ticket', 'backupino-portal' ); ?></button>
					<button type="button" onclick="bkAdminCloseTicket();" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">&times;</button>
				</div>
			</div>

			<!-- Conversation stream with user/staff bubbles -->
			<div id="op-messages-stream" style="max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
			</div>

			<!-- Reply & Status update form -->
			<form id="op-reply-form" onsubmit="bkAdminSubmitReply(event);" enctype="multipart/form-data">
				<input type="hidden" id="op-reply-ticket-id" value="0">

				<div style="margin-bottom: 12px;">
					<label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;"><?php esc_html_e( 'Official Staff Response:', 'backupino-portal' ); ?></label>
					<textarea id="op-reply-message" rows="4" style="width: 100%; border-radius: 6px; border: 1px solid #cbd5e1; padding: 10px; font-family: inherit; font-size: 13.5px;" placeholder="<?php esc_attr_e( 'Type official response here...', 'backupino-portal' ); ?>"></textarea>
				</div>

				<div style="margin-bottom: 14px;">
					<label style="display: block; font-weight: 600; font-size: 12.5px; margin-bottom: 4px; color: #475569;">
						📎 <?php esc_html_e( 'Attach File (JPG, PNG, PDF, TXT, LOG, ZIP - max 10MB):', 'backupino-portal' ); ?>
					</label>
					<input type="file" id="op-reply-attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.log,.zip" style="font-size: 12px;">
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
					<div style="display: flex; align-items: center; gap: 8px;">
						<label style="font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Update status to:', 'backupino-portal' ); ?></label>
						<select id="op-reply-new-status" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 4px 8px;">
							<option value="waiting_customer"><?php esc_html_e( 'Waiting Customer', 'backupino-portal' ); ?></option>
							<option value="in_progress"><?php esc_html_e( 'In Progress', 'backupino-portal' ); ?></option>
							<option value="resolved"><?php esc_html_e( 'Resolved', 'backupino-portal' ); ?></option>
							<option value="closed"><?php esc_html_e( 'Closed', 'backupino-portal' ); ?></option>
						</select>
					</div>

					<div style="display: flex; gap: 8px;">
						<button type="button" class="button button-secondary" onclick="bkAdminCloseTicket();"><?php esc_html_e( 'Cancel', 'backupino-portal' ); ?></button>
						<button type="submit" id="btn-op-submit" class="button button-primary"><?php esc_html_e( 'Send Official Reply', 'backupino-portal' ); ?></button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<script>
		const BK_OP_NONCE = '<?php echo esc_js( $nonce ); ?>';

		function bkAdminOpenTicket(ticketId) {
			const modal = document.getElementById('bk-operator-ticket-modal');
			const stream = document.getElementById('op-messages-stream');
			stream.innerHTML = '<p style="text-align: center; color: #64748b;">Loading ticket conversation...</p>';
			document.getElementById('op-reply-ticket-id').value = ticketId;
			document.getElementById('op-reply-message').value = '';
			document.getElementById('op-reply-attachment').value = '';
			modal.style.display = 'flex';

			const data = new URLSearchParams();
			data.append('action', 'backupino_operator_get_ticket');
			data.append('_wpnonce', BK_OP_NONCE);
			data.append('ticket_id', ticketId);

			fetch(ajaxurl, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						const t = res.data;
						document.getElementById('op-modal-title').textContent = t.subject;
						document.getElementById('op-modal-meta').textContent = t.ticket_number + ' | ' + t.customer_name + ' (' + t.customer_email + ')';
						document.getElementById('op-reply-new-status').value = t.status === 'open' ? 'waiting_customer' : t.status;

						if (!t.replies || t.replies.length === 0) {
							stream.innerHTML = '<p style="text-align: center; color: #64748b;">No messages yet.</p>';
							return;
						}

						let html = '';
						t.replies.forEach(r => {
							const isStaff = r.is_staff;
							const alignClass = isStaff ? 'staff' : 'user';
							const badge = isStaff ? '<span style="background: #0284c7; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 4px;">Staff</span>' : '<span style="background: #e2e8f0; color: #334155; font-size: 10px; padding: 2px 6px; border-radius: 4px;">Customer</span>';

							let attHtml = '';
							if (r.attachment_url) {
								attHtml = `<a href="${r.attachment_url}" target="_blank" rel="noopener noreferrer" class="bk-attachment-btn">📎 ${r.attachment_name || 'View Attachment'}</a>`;
							}

							html += `
								<div class="bk-chat-bubble ${alignClass}">
									<div class="bk-bubble-header">
										<div style="display: flex; align-items: center; gap: 8px;">
											<strong>${r.sender_name}</strong>
											${badge}
										</div>
										<span style="font-size: 11px; color: #64748b;">${r.created_at}</span>
									</div>
									<div style="font-size: 13.5px; color: #0f172a; white-space: pre-wrap; line-height: 1.6;">${r.message}</div>
									${attHtml}
								</div>
							`;
						});
						stream.innerHTML = html;
						stream.scrollTop = stream.scrollHeight;
					} else {
						stream.innerHTML = '<p style="color: #dc2626;">' + (res.data.message || 'Error loading ticket.') + '</p>';
					}
				});
		}

		function bkAdminDirectCloseTicket() {
			const ticketId = document.getElementById('op-reply-ticket-id').value;
			if (!ticketId || !confirm('Close this ticket?')) return;

			const data = new URLSearchParams();
			data.append('action', 'backupino_operator_update_ticket_status');
			data.append('_wpnonce', BK_OP_NONCE);
			data.append('ticket_id', ticketId);
			data.append('status', 'closed');

			fetch(ajaxurl, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert('Ticket closed.');
						location.reload();
					} else {
						alert(res.data.message || 'Error closing ticket.');
					}
				});
		}

		function bkAdminCloseTicket() {
			document.getElementById('bk-operator-ticket-modal').style.display = 'none';
		}

		function bkAdminSubmitReply(e) {
			e.preventDefault();
			const ticketId = document.getElementById('op-reply-ticket-id').value;
			const msg = document.getElementById('op-reply-message').value.trim();
			const status = document.getElementById('op-reply-new-status').value;
			const fileInput = document.getElementById('op-reply-attachment');

			if (!msg && (!fileInput.files || fileInput.files.length === 0)) {
				alert('Please enter a message or attach a file.');
				return;
			}

			const btn = document.getElementById('btn-op-submit');
			btn.disabled = true;
			btn.textContent = 'Sending...';

			const formData = new FormData();
			formData.append('action', 'backupino_operator_reply_ticket');
			formData.append('_wpnonce', BK_OP_NONCE);
			formData.append('ticket_id', ticketId);
			formData.append('message', msg);
			formData.append('new_status', status);

			if (fileInput.files && fileInput.files.length > 0) {
				formData.append('attachment', fileInput.files[0]);
			}

			fetch(ajaxurl, { method: 'POST', body: formData })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					btn.textContent = 'Send Official Reply';
					if (res.success) {
						alert(res.data.message || 'Reply sent.');
						document.getElementById('op-reply-message').value = '';
						document.getElementById('op-reply-attachment').value = '';
						bkAdminOpenTicket(ticketId);
					} else {
						alert(res.data.message || 'Error sending reply.');
					}
				})
				.catch(() => {
					btn.disabled = false;
					btn.textContent = 'Send Official Reply';
					alert('Network error.');
				});
		}
	</script>
</div>
