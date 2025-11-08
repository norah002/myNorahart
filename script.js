function openForm(friendName) {
    document.getElementById('infoForm').style.display = 'block';
    document.getElementById('friendName').textContent = `معلومات ${friendName}`;
    document.getElementById('currentFriend').value = friendName;
}

function closeForm() {
    document.getElementById('infoForm').style.display = 'none';
    document.getElementById('friendForm').reset();
}

// إغلاق النموذع عند النقر خارج المحتوى
window.onclick = function(event) {
    const form = document.getElementById('infoForm');
    if (event.target === form) {
        closeForm();
    }
}

// معالجة إرسال النموذج
document.getElementById('friendForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('save_info.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('تم حفظ المعلومات بنجاح! 🎉');
            closeForm();
        } else {
            alert('حدث خطأ في حفظ المعلومات: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الإرسال');
    });
});// يمكن إضافة أي تفاعلات إضافية هنا
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded');
});