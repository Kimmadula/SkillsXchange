@if(config('ratings.enabled') && config('ratings.contexts.session'))
<div id="session-rating-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
	<div style="max-width: 520px; width: 92%; margin: 10vh auto; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
		<div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
			<h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #111827;">Rate Your Session</h3>
			<button type="button" onclick="closeSessionRatingModal()" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280;">×</button>
		</div>
		<form id="session-rating-form">
			@csrf
			<input type="hidden" id="session-rating-trade-id" name="trade_id" value="">
			<input type="hidden" id="session-rating-rated-user-id" name="rated_user_id" value="">
			<input type="hidden" id="session-rating-session-type" name="session_type" value="trade_session">
			<input type="hidden" id="session-rating-duration" name="session_duration" value="">
			
			<div style="padding: 16px 20px;">
				<div style="margin-bottom: 16px; font-size: 0.95rem; color: #374151;">
					Please rate your experience with this skill sharing session.
				</div>
				<div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
					<div>
						<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Overall Experience</label>
						<div class="rating-stars" data-rating="overall_rating">
							<span class="star" data-value="1">★</span>
							<span class="star" data-value="2">★</span>
							<span class="star" data-value="3">★</span>
							<span class="star" data-value="4">★</span>
							<span class="star" data-value="5">★</span>
						</div>
						<input type="hidden" name="overall_rating" value="0">
					</div>
					<div>
						<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Communication</label>
						<div class="rating-stars" data-rating="communication_rating">
							<span class="star" data-value="1">★</span>
							<span class="star" data-value="2">★</span>
							<span class="star" data-value="3">★</span>
							<span class="star" data-value="4">★</span>
							<span class="star" data-value="5">★</span>
						</div>
						<input type="hidden" name="communication_rating" value="0">
					</div>
					<div>
						<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Helpfulness</label>
						<div class="rating-stars" data-rating="helpfulness_rating">
							<span class="star" data-value="1">★</span>
							<span class="star" data-value="2">★</span>
							<span class="star" data-value="3">★</span>
							<span class="star" data-value="4">★</span>
							<span class="star" data-value="5">★</span>
						</div>
						<input type="hidden" name="helpfulness_rating" value="0">
					</div>
					<div>
						<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Knowledge</label>
						<div class="rating-stars" data-rating="knowledge_rating">
							<span class="star" data-value="1">★</span>
							<span class="star" data-value="2">★</span>
							<span class="star" data-value="3">★</span>
							<span class="star" data-value="4">★</span>
							<span class="star" data-value="5">★</span>
						</div>
						<input type="hidden" name="knowledge_rating" value="0">
					</div>
					<div>
						<label style="display: block; font-size: 0.9rem; color: #374151; margin-bottom: 6px;">Additional Feedback (optional)</label>
						<textarea name="written_feedback" placeholder="Share your thoughts about the session..." style="width: 100%; min-height: 88px; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px; resize: vertical; font-family: inherit;"></textarea>
					</div>
				</div>
			</div>
			<div style="padding: 12px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
				<button type="button" onclick="closeSessionRatingModal()" style="background: #e5e7eb; color: #111827; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; cursor: pointer;">Skip</button>
				<button type="submit" id="session-rating-submit" style="background: #3b82f6; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; cursor: pointer;">Submit Rating</button>
			</div>
		</form>
	</div>
</div>

<style>
.rating-stars {
	display: flex;
	gap: 4px;
	font-size: 1.5rem;
}

.rating-stars .star {
	cursor: pointer;
	color: #d1d5db;
	transition: color 0.2s ease;
	user-select: none;
}

.rating-stars .star:hover,
.rating-stars .star.active {
	color: #fbbf24;
}

.rating-stars .star:hover ~ .star {
	color: #d1d5db;
}
</style>

<script>
// Session Rating Modal Functions
function showSessionRatingModal(tradeId, ratedUserId, sessionDuration = 0) {
	const modal = document.getElementById('session-rating-modal');
	if (!modal) return;
	
	// Set form data
	document.getElementById('session-rating-trade-id').value = tradeId || '';
	document.getElementById('session-rating-rated-user-id').value = ratedUserId || '';
	document.getElementById('session-rating-duration').value = sessionDuration;
	
	// Reset form
	document.getElementById('session-rating-form').reset();
	resetAllStarRatings();
	
	// Show modal
	modal.style.display = 'block';
}

function closeSessionRatingModal() {
	const modal = document.getElementById('session-rating-modal');
	if (modal) {
		modal.style.display = 'none';
		
		// If this is called after session completion, redirect to ongoing trades
		// Check if we're in a completed session by looking for session completion indicators
		if (document.getElementById('session-status') && 
			document.getElementById('session-status').textContent.includes('Completed')) {
			setTimeout(() => {
				window.location.href = '{{ route("trades.ongoing") }}';
			}, 1000);
		}
	}
}

function resetAllStarRatings() {
	document.querySelectorAll('.rating-stars').forEach(ratingGroup => {
		ratingGroup.querySelectorAll('.star').forEach(star => {
			star.classList.remove('active');
		});
	});
}

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
	// Initialize star ratings
	document.querySelectorAll('.rating-stars').forEach(ratingGroup => {
		ratingGroup.querySelectorAll('.star').forEach(star => {
			star.addEventListener('click', function() {
				const value = parseInt(this.dataset.value);
				const ratingType = ratingGroup.dataset.rating;
				
				// Update visual state
				ratingGroup.querySelectorAll('.star').forEach((s, index) => {
					s.classList.toggle('active', index < value);
				});
				
				// Update hidden input
				const hiddenInput = document.querySelector(`input[name="${ratingType}"]`);
				if (hiddenInput) {
					hiddenInput.value = value;
				}
			});
		});
	});
	
	// Handle form submission
	document.getElementById('session-rating-form').addEventListener('submit', function(e) {
		e.preventDefault();
		
		const submitBtn = document.getElementById('session-rating-submit');
		const originalText = submitBtn.textContent;
		
		// Disable button and show loading
		submitBtn.disabled = true;
		submitBtn.textContent = 'Submitting...';
		
		// Collect form data
		const formData = new FormData(this);
		
		// Submit rating
		fetch('{{ route("session.rating.store") }}', {
			method: 'POST',
			headers: {
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			body: JSON.stringify({
				trade_id: formData.get('trade_id'),
				rated_user_id: formData.get('rated_user_id'),
				session_type: formData.get('session_type'),
				overall_rating: parseInt(formData.get('overall_rating')),
				communication_rating: parseInt(formData.get('communication_rating')),
				helpfulness_rating: parseInt(formData.get('helpfulness_rating')),
				knowledge_rating: parseInt(formData.get('knowledge_rating')),
				written_feedback: formData.get('written_feedback'),
				session_duration: parseInt(formData.get('session_duration'))
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Show success message
				if (typeof showSuccess === 'function') {
					showSuccess('Thank you for your feedback!');
				} else {
					alert('Thank you for your feedback!');
				}
				
				// Close modal
				closeSessionRatingModal();
				
				// Redirect to ongoing trades page after a short delay
				setTimeout(() => {
					window.location.href = '{{ route("trades.ongoing") }}';
				}, 1500);
			} else {
				// Show error message
				if (typeof showError === 'function') {
					showError(data.message || 'Failed to submit rating');
				} else {
					alert(data.message || 'Failed to submit rating');
				}
			}
		})
		.catch(error => {
			console.error('Rating submission error:', error);
			if (typeof showError === 'function') {
				showError('Failed to submit rating. Please try again.');
			} else {
				alert('Failed to submit rating. Please try again.');
			}
		})
		.finally(() => {
			// Re-enable button
			submitBtn.disabled = false;
			submitBtn.textContent = originalText;
		});
	});
});
</script>
@endif


