// Enable Bootstrap Tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

// Delegated handlers for view - handle all tables in the page
const table = document;

const viewModal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
const memberDetails = document.getElementById('memberDetails');

function rowFor(memberId) {
	return document.querySelector(`tr[data-member-id="${memberId}"]`);
}

async function fetchMember(memberId) {
	const res = await fetch(`members_actions.php?action=view&member_id=${encodeURIComponent(memberId)}`);
	return res.json();
}

// View
table.addEventListener('click', async (e) => {
	const btn = e.target.closest('.btn-view');
	if (!btn) return;
	const memberId = btn.dataset.memberId;
	
	// Show loading state
	document.getElementById('memberDetails').innerHTML = `
		<div class="text-center py-5">
			<div class="spinner-border text-primary" role="status">
				<span class="visually-hidden">Loading...</span>
			</div>
			<p class="mt-3 text-muted">Loading member details...</p>
		</div>
	`;
	
	viewModal.show();
	
	const json = await fetchMember(memberId);
	if (!json.success) { 
		document.getElementById('memberDetails').innerHTML = `
			<div class="alert alert-danger">
				<i class="fas fa-exclamation-triangle me-2"></i>
				${json.message || 'Failed to load member data'}
			</div>
		`;
		return; 
	}
	
	const m = json.member;
	const isExpired = m.expired_date && new Date(m.expired_date) < new Date();
	
	// Calculate membership duration text
	let durationText = '';
	if (m.membership_type === 'regular' && m.membership_duration) {
		durationText = m.membership_duration == 12 ? '1 Year' : `${m.membership_duration} Month${m.membership_duration > 1 ? 's' : ''}`;
	} else if (m.membership_type === 'session') {
		durationText = '1 Day';
	}
	
	memberDetails.innerHTML = `
		<div class="profile-header">
			<div class="profile-avatar">
				${m.photo ? `<img src="../../uploads/member_photos/${m.photo}" alt="Profile Picture" class="profile-picture" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">` : '<i class="fas fa-user"></i>'}
				${m.photo ? '<i class="fas fa-user" style="display:none;"></i>' : ''}
			</div>
			<div class="profile-info">
				<h4>${m.first_name} ${m.last_name}</h4>
				<p class="member-id">Member #${m.member_id}</p>
				<div class="status-badge ${isExpired ? 'expired' : 'active'}">
					<i class="fas fa-circle"></i>
					${isExpired ? 'Expired' : 'Active'}
				</div>
				${m.photo ? `<small class="text-muted">Photo: ${m.photo}</small>` : '<small class="text-muted">No profile picture uploaded</small>'}
			</div>
		</div>
		
		<div class="info-grid">
			<div class="info-card">
				<div class="card-header">
					<i class="fas fa-user"></i>
					<span>Personal Information</span>
				</div>
				<div class="card-content">
					<div class="info-item">
						<label>Email Address</label>
						<span>${m.email}</span>
					</div>
					<div class="info-item">
						<label>Phone Number</label>
						<span>${m.phone || 'Not provided'}</span>
					</div>
					<div class="info-item">
						<label>Gender</label>
						<span>${m.gender || 'Not specified'}</span>
					</div>
				</div>
			</div>
			
			<div class="info-card">
				<div class="card-header">
					<i class="fas fa-id-card"></i>
					<span>Membership Details</span>
				</div>
				<div class="card-content">
					<div class="info-item">
						<label>Membership Type</label>
						<span class="membership-type">${m.membership_type.charAt(0).toUpperCase() + m.membership_type.slice(1)}</span>
					</div>
					<div class="info-item">
						<label>Duration</label>
						<span>${m.membership_duration ? m.membership_duration + ' Month(s)' : 'Not specified'}</span>
					</div>
					<div class="info-item">
						<label>Join Date</label>
						<span>${new Date(m.join_date).toLocaleDateString()}</span>
					</div>
					<div class="info-item">
						<label>Expiry Date</label>
						<span class="${isExpired ? 'expired' : ''}">${new Date(m.expired_date).toLocaleDateString()}</span>
					</div>
				</div>
			</div>
			
			<div class="info-card full-width">
				<div class="card-header">
					<i class="fas fa-map-marker-alt"></i>
					<span>Address</span>
				</div>
				<div class="card-content">
					<div class="address-content">
						${m.address || 'No address provided'}
					</div>
				</div>
			</div>
		</div>
	`;
});
