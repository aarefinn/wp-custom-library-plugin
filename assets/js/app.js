const { render, useState, useEffect } = wp.element;
const { Button } = wp.components;

const App = () => {
    const [books, setBooks] = useState([]);
    const [selectedBook, setSelectedBook] = useState(null);
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        author: '',
        publicationyear: '',
        status: 'available'
    });
    const [loading, setLoading] = useState(false);
    const [showForm, setShowForm] = useState(false);

    const fetchBooks = async () => {
        setLoading(true);
        const response = await wp.apiFetch({ path: '/library/v1/books' });
        setBooks(response);
        setLoading(false);
    };

    useEffect(() => {
        fetchBooks();
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        
        const path = selectedBook ? `/library/v1/books/${selectedBook.id}` : '/library/v1/books';
        const method = selectedBook ? 'PUT' : 'POST';
        
        await wp.apiFetch({
            path,
            method,
            data: formData
        });
        
        setFormData({ title: '', description: '', author: '', publicationyear: '', status: 'available' });
        setSelectedBook(null);
        setShowForm(false);
        fetchBooks();
        setLoading(false);
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure?')) return;
        await wp.apiFetch({ path: `/library/v1/books/${id}`, method: 'DELETE' });
        fetchBooks();
    };

    const handleEdit = (book) => {
        setSelectedBook(book);
        setFormData({
            title: book.title,
            description: book.description || '',
            author: book.author || '',
            publicationyear: book.publicationyear || '',
            status: book.status
        });
        setShowForm(true);
    };

    if (loading && books.length === 0) return <div>Loading...</div>;

    return (
        <div style={{ padding: '20px' }}>
            <div style={{ marginBottom: '20px' }}>
                <Button 
                    isPrimary 
                    onClick={() => {
                        setSelectedBook(null);
                        setFormData({ title: '', description: '', author: '', publicationyear: '', status: 'available' });
                        setShowForm(true);
                    }}
                >
                    Add New Book
                </Button>
                <Button onClick={fetchBooks} style={{ marginLeft: '10px' }}>Refresh</Button>
            </div>

            {showForm && (
                <div style={{ background: '#f9f9f9', padding: '20px', marginBottom: '20px', borderRadius: '5px' }}>
                    <h3>{selectedBook ? 'Edit Book' : 'Add Book'}</h3>
                    <form onSubmit={handleSubmit}>
                        <input
                            type="text"
                            placeholder="Title *"
                            value={formData.title}
                            onChange={(e) => setFormData({...formData, title: e.target.value})}
                            style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                            required
                        />
                        <textarea
                            placeholder="Description"
                            value={formData.description}
                            onChange={(e) => setFormData({...formData, description: e.target.value})}
                            style={{ width: '100%', marginBottom: '10px', padding: '8px', height: '80px' }}
                        />
                        <input
                            type="text"
                            placeholder="Author"
                            value={formData.author}
                            onChange={(e) => setFormData({...formData, author: e.target.value})}
                            style={{ width: '48%', marginBottom: '10px', padding: '8px' }}
                        />
                        <input
                            type="number"
                            placeholder="Year"
                            value={formData.publicationyear}
                            onChange={(e) => setFormData({...formData, publicationyear: e.target.value})}
                            style={{ width: '48%', marginBottom: '10px', padding: '8px' }}
                        />
                        <select
                            value={formData.status}
                            onChange={(e) => setFormData({...formData, status: e.target.value})}
                            style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                        >
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                        <div>
                            <Button isPrimary type="submit" disabled={loading}>
                                {loading ? 'Saving...' : (selectedBook ? 'Update' : 'Create')}
                            </Button>
                            <Button onClick={() => setShowForm(false)} style={{ marginLeft: '10px' }}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>
            )}

            <h3>Books ({books.length})</h3>
            <div style={{ display: 'grid', gap: '10px' }}>
                {books.map(book => (
                    <div key={book.id} style={{
                        border: '1px solid #ddd',
                        padding: '15px',
                        borderRadius: '5px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                    }}>
                        <div>
                            <h4>{book.title}</h4>
                            <p><strong>Author:</strong> {book.author || 'N/A'}</p>
                            <p><strong>Year:</strong> {book.publicationyear || 'N/A'}</p>
                            <p><strong>Status:</strong> 
                                <span style={{
                                    color: book.status === 'available' ? 'green' : book.status === 'borrowed' ? 'orange' : 'red',
                                    fontWeight: 'bold'
                                }}>
                                    {book.status}
                                </span>
                            </p>
                        </div>
                        <div>
                            <Button isSecondary onClick={() => handleEdit(book)}>Edit</Button>
                            <Button 
                                isDestructive 
                                onClick={() => handleDelete(book.id)}
                                style={{ marginLeft: '5px' }}
                            >
                                Delete
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

// Render app
const root = wp.element.createRoot(document.getElementById('lm-root'));
root.render(<App />);
