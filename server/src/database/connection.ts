import mongoose from 'mongoose';

export async function connectDatabase(): Promise<void> {
  const mongoUri = process.env.MONGODB_URI || 'mongodb://localhost:27017/nmon';
  
  try {
    await mongoose.connect(mongoUri, {
      // Mongoose 7+ uses these defaults
    });
    console.log('✅ Connected to MongoDB');
  } catch (error) {
    console.error('❌ MongoDB connection error:', error);
    process.exit(1);
  }
}
