@if(config('ratings.enabled') && config('ratings.contexts.session'))
<div id="session-rating-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
	<div style="max-width: 520px; width: 92%; margin: 10vh auto; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
		<div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
			<h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #111827;">Rate Your Session</h3>
			<button type="button" onclick="document.getElementById('session-rating-modal').style.display='none'" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280;">×</button>
		</div>
		<div style="padding: 16px 20px;">
			<div style="margin-bottom: 12px; font-size: 0.95rem; color: #374151;">
				This feature is coming soon. You'll be able to rate your experience after ending a session.
			</div>
			<div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Overall Experience</label>
					<div style="color: #d1d5db; font-size: 1.25rem;">★★★★★</div>
				</div>
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Communication</label>
					<div style="color: #d1d5db; font-size: 1.25rem;">★★★★★</div>
				</div>
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Helpfulness</label>
					<div style="color: #d1d5db; font-size: 1.25rem;">★★★★★</div>
				</div>
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Knowledge</label>
					<div style="color: #d1d5db; font-size: 1.25rem;">★★★★★</div>
				</div>
				<div>
					<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Additional Feedback (optional)</label>
					<textarea disabled placeholder="Coming soon..." style="width: 100%; min-height: 88px; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px; background: #f9fafb; color: #9ca3af;"></textarea>
				</div>
			</div>
		</div>
		<div style="padding: 12px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
			<button type="button" onclick="document.getElementById('session-rating-modal').style.display='none'" style="background: #e5e7eb; color: #111827; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; cursor: pointer;">Close</button>
			<button type="button" disabled title="Coming soon" style="background: #3b82f6; color: white; opacity: 0.6; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; cursor: not-allowed;">Submit</button>
		</div>
	</div>
</div>
@endif


