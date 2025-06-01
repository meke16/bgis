function viewProfile(id) {
    $.when(
        $.get('teachers.php', { get_teacher: 1, id: id }),
        $.get('teachers.php', { get_subjects: 1 })
    ).done(function(teacherResponse, subjectsResponse) {
        try {
            const teacher = JSON.parse(teacherResponse[0]);
            const subjects = JSON.parse(subjectsResponse[0]);

            if (teacher.error) {
                alert(teacher.error);
                return;
            }


            // Subject name lookup
            function getSubjectNameById(id) {
                const subject = subjects.find(s => s.id == id); // Note the double equals (==) for type-coercion if needed
                return subject ? subject.name : 'N/A';
            }

            let assignmentsHtml = '';
            if (teacher.assignments && teacher.assignments.length > 0) {
                assignmentsHtml = teacher.assignments.map((assignment, index) => `
                    <div class="assignment-detail mb-3">
                        <h6>Assignment ${index + 1}</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Grade:</strong> ${assignment.grade || 'N/A'}
                            </div>
                            <div class="col-md-3">
                                <strong>Section:</strong> ${assignment.section || 'N/A'}
                            </div>
                            <div class="col-md-3">
                                <strong>Subject:</strong> ${getSubjectNameById(assignment.subject_id)}
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                assignmentsHtml = '<p>No assignments found</p>';
            }

            const profileHtml = `
                <div class="profile-header text-center">
                    <img src="${teacher.photo}" class="profile-img mb-3" alt="Teacher Photo">
                    <h3>${teacher.name}</h3>
                </div>
                <div class="profile-details mt-4">
                    <div class="row detail-row">
                        <div class="col-md-3 detail-label">Gender:</div>
                        <div class="col-md-9">${teacher.sex || 'N/A'}</div>
                    </div>
                    <div class="row detail-row">
                        <div class="col-md-3 detail-label">Username:</div>
                        <div class="col-md-9">${teacher.username || 'N/A'}</div>
                    </div>
                    <div class="row detail-row">
                        <div class="col-md-3 detail-label">Contact:</div>
                        <div class="col-md-9">${teacher.contact || 'N/A'}</div>
                    </div>
                    <div class="row detail-row">
                        <div class="col-md-12 detail-label">Assignments:</div>
                        <div class="col-md-12">
                            ${assignmentsHtml}
                        </div>
                    </div>
                </div>
            `;

            $('#profileContent').html(profileHtml);
            $('#profileOverlay').show();
        } catch (e) {
            console.error('Error parsing profile data:', e);
            alert('Error loading teacher profile');
        }
    }).fail(function() {
        alert('Failed to load teacher profile or subjects');
    });
}


// Close profile view
function closeProfile() {
    $('#profileOverlay').hide();
}